<?php

namespace App\Http\Controllers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Google\Client as GoogleClient;
use Google\Service\Sheets;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        /* ---------- default ---------- */
        $onus        = [];
        $connections = [];
        $totalAp     = 0;
        $userOnline  = 0;
        $totalUser   = 0;
        $logActivity = collect();

        /* ---------- hitung user & total AP ---------- */
        try {
            $responseOnu  = Http::timeout(5)->get('http://172.16.105.26:6767/api/onu');
            $responseConn = Http::timeout(5)->get('http://172.16.105.26:6767/api/onu/connect');

            if ($responseOnu->ok()) {
                $onus    = $responseOnu->json();
                $totalAp = is_array($onus) ? count($onus) : 0;
            }

            if ($responseConn->ok()) {
                $connections = $responseConn->json();
                if (is_array($connections)) {
                    foreach ($connections as $c) {
                        if (!is_array($c) || !isset($c['wifiClients'])) {
                            continue;
                        }
                        $wifi = $c['wifiClients'];
                        $userOnline += count($wifi['5G']    ?? []);
                        $userOnline += count($wifi['2_4G'] ?? []);
                        $userOnline += count($wifi['unknown'] ?? []);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('DashboardController@index : '.$e->getMessage());
        }
        $totalUser = $userOnline;

        /* ---------- log activity (dummy) ---------- */
        if (is_array($connections)) {
            foreach ($connections as $c) {
                foreach ($c['wifiClients']['unknown'] ?? [] as $cl) {
                    $logActivity->push((object)[
                        'time'   => now()->subMinutes(rand(1,30)),
                        'user'   => $cl['wifi_terminal_name'] ?? 'Unknown',
                        'ap'     => $c['sn'] ?? null,
                        'action' => 'connected',
                    ]);
                }
            }
        }

        /* ---------- Google-Sheet sebagai sumber utama chart ---------- */
        $dayLabels  = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];
        $weeklyData = array_fill(0, 7, 0);

        $sheetRows  = cache()->remember('sheet_users_weekly', 300,
                                        fn() => $this->readSheetWeekly());

        foreach ($sheetRows as $idx => $row) {
            if (isset($row[0]) && is_numeric($row[0])) {
                $weeklyData[$idx] = (int) $row[0];
            }
        }

        /* fallback DB mingguan kalau Sheet kosong */
        if (array_sum($weeklyData) === 0) {
            $today  = now();
            $monday = $today->copy()->startOfWeek();
            $stats  = DB::table('daily_user_stats')
                        ->whereBetween('date', [$monday, $today])
                        ->pluck('user_count', 'date');

            foreach ($dayLabels as $i => $day) {
                $date = $monday->copy()->addDays($i)->toDateString();
                $weeklyData[$i] = (int) ($stats[$date] ?? 0);
            }
        }

        $dailyUsers = [
            'labels' => $dayLabels,
            'data'   => $weeklyData,
        ];

        /* ---------- baca Sheet paket 110 & 200 untuk mapping SN ---------- */
        $ontMap = cache()->remember('ont_map_paket_all', 600,
                                    fn() => $this->readOntMap());

        /* ---------- susun clients + detail lokasi dari Sheet ---------- */
        $allClients = collect();
        if (is_array($connections)) {
            foreach ($connections as $c) {
                $sn   = strtoupper(trim($c['sn'] ?? ''));
                $info = $ontMap[$sn] ?? null;   // detail lokasi

                $clients = array_merge(
                    $c['wifiClients']['5G']    ?? [],
                    $c['wifiClients']['2_4G']  ?? [],
                    $c['wifiClients']['unknown'] ?? []
                );

                foreach ($clients as $cl) {
                    $cl['ap_sn']         = $c['sn']           ?? null;
                    $cl['ap_name']       = $info['location']  ?? '-';
                    $cl['ap_kemantren']  = $info['kemantren'] ?? '-';
                    $cl['ap_kelurahan']  = $info['kelurahan'] ?? '-';
                    $cl['ap_rt']         = $info['rt']        ?? '-';
                    $cl['ap_rw']         = $info['rw']        ?? '-';
                    $cl['ap_ip']         = $info['ip']        ?? '-';
                    $cl['ap_pic']        = $info['pic']       ?? '-';
                    $cl['ap_coordinate'] = $info['coordinate'] ?? '-';
                    $allClients->push($cl);
                }
            }
        }

        // Paginate flat clients list (keperluan legacy / opsional)
        $perPage = request('perPage', 10);
        $page    = request('page', 1);
        $clients = new LengthAwarePaginator(
            $allClients->forPage($page, $perPage),
            $allClients->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        // Build locations collection grouped by AP SN and sort by connected users desc
        $locationsCollection = collect();
        foreach ($allClients->groupBy('ap_sn') as $sn => $group) {
            $first = $group->first();
            $locationsCollection->push([
                'sn' => $sn,
                'location' => $first['ap_name'] ?? '-','kemantren' => $first['ap_kemantren'] ?? '-','kelurahan' => $first['ap_kelurahan'] ?? '-','rt' => $first['ap_rt'] ?? '-','rw' => $first['ap_rw'] ?? '-',
                'clients' => $group->values()->all(),
                'count' => $group->count(),
            ]);
        }

        $locationsSorted = $locationsCollection->sortByDesc('count')->values();

        $locPerPage = request('locPerPage', 5);
        $locPage = request('locPage', 1);
        $locations = new LengthAwarePaginator(
            $locationsSorted->forPage($locPage, $locPerPage),
            $locationsSorted->count(),
            $locPerPage,
            $locPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        // Get unique kemantren list untuk filter
        $kemantrenList = $locationsCollection->pluck('kemantren')->unique()->sort()->values();

        // Get data rekap per lokasi (mingguan/bulanan)
        $today = now();
        $monday = $today->copy()->startOfWeek();
        $firstDayOfMonth = $today->copy()->startOfMonth();
        $currentMonth = $today->month;
        $currentYear = $today->year;

        // Data per hari (Senin-Minggu) untuk mingguan
        $dayLabels = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
        $weeklyLocationByDay = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $monday->copy()->addDays($i);
            $weeklyLocationByDay[$date->toDateString()] = DB::table('daily_location_stats')
                ->where('date', $date->toDateString())
                ->groupBy('location', 'kemantren')
                ->selectRaw('location, kemantren, SUM(user_count) as total')
                ->get()
                ->toArray();
        }

        // Aggregate untuk bulan ini (default current month)
        $monthlyLocationData = DB::table('daily_location_stats')
            ->whereYear('date', $currentYear)
            ->whereMonth('date', $currentMonth)
            ->whereBetween('date', [$firstDayOfMonth, $today])
            ->groupBy('location', 'kemantren')
            ->selectRaw('location, kemantren, SUM(user_count) as total')
            ->get()
            ->toArray();

        // Semua bulan untuk dropdown
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return view('dashboard', compact(
            'totalUser','totalAp','userOnline','logActivity','clients','dailyUsers','locations',
            'kemantrenList','monthlyLocationData','dayLabels','weeklyLocationByDay',
            'months','currentMonth','currentYear'
        ));
    }

    /**
     * Return monthly aggregated location data as JSON for given month/year
     */
    public function monthlyLocationData(Request $request)
    {
        $month = (int) $request->query('month', now()->month);
        $year  = (int) $request->query('year', now()->year);

        $start = Carbon::createFromDate($year, $month, 1)->toDateString();
        $end   = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();

        $today = now()->toDateString();
        if ($end > $today) {
            $end = $today;
        }

        $data = DB::table('daily_location_stats')
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->whereBetween('date', [$start, $end])
            ->groupBy('location', 'kemantren')
            ->selectRaw('location, kemantren, SUM(user_count) as total')
            ->get();

        return response()->json($data);
    }

    /* ---------- Google Client ---------- */
    private function getClient(): GoogleClient
    {
        $client = new GoogleClient;
        $client->setApplicationName('Laravel Dashboard');
        $client->setScopes([Sheets::SPREADSHEETS_READONLY]);
        $client->setAuthConfig(config_path('google/service-accounts.json'));
        return $client;
    }

    /* ---------- Baca Sheet mingguan (7 baris) ---------- */
    private function readSheetWeekly(): array
    {
        $service = new Sheets($this->getClient());
        $id      = config('services.google.sheet_id');
        $range   = 'Rapi1!B4:B10';   // 7 cell = SenMin
        return $service->spreadsheets_values->get($id, $range)->getValues() ?? [];
    }

    /* ---------- Mapping SN -> lokasi (paket 110 & 200) ---------- */
    private function readOntMap(): array
    {
        $service = new Sheets($this->getClient());
        $map = [];

        // Baca paket 110 dari sheet terpisah
        $paket110_id = '1Wtkfylu-BbdIzvV7ZT_M7rEOg2ANBh5ylvea1sp37m8';
        try {
            $range = "'paket 110'!B2:I201";
            $rows  = $service->spreadsheets_values->get($paket110_id, $range)->getValues() ?? [];
            
            foreach ($rows as $row) {
                $sn = trim($row[7] ?? '');
                if ($sn === '') continue;

                $map[strtoupper($sn)] = [
                    'location'   => trim($row[0] ?? ''),
                    'kemantren'  => trim($row[1] ?? ''),
                    'kelurahan'  => trim($row[2] ?? ''),
                    'rt'         => trim($row[3] ?? ''),
                    'rw'         => trim($row[4] ?? ''),
                    'ip'         => trim($row[5] ?? ''),
                    'pic'        => trim($row[6] ?? ''),
                    'coordinate' => '',
                ];
            }
        } catch (\Throwable $e) {
            Log::error('readOntMap - paket 110: ' . $e->getMessage());
        }

        // Baca paket 200 dari sheet lama
        try {
            $id    = config('services.google.sheet_id');
            $range = "'paket 200'!B2:I201";
            $rows  = $service->spreadsheets_values->get($id, $range)->getValues() ?? [];

            foreach ($rows as $row) {
                $sn = trim($row[7] ?? '');
                if ($sn === '') continue;

                $map[strtoupper($sn)] = [
                    'location'   => trim($row[0] ?? ''),
                    'kemantren'  => trim($row[1] ?? ''),
                    'kelurahan'  => trim($row[2] ?? ''),
                    'rt'         => trim($row[3] ?? ''),
                    'rw'         => trim($row[4] ?? ''),
                    'ip'         => trim($row[5] ?? ''),
                    'pic'        => trim($row[6] ?? ''),
                    'coordinate' => trim($row[8] ?? '') ?? '',
                ];
            }
        } catch (\Throwable $e) {
            Log::error('readOntMap - paket 200: ' . $e->getMessage());
        }

        return $map;
    }
}
