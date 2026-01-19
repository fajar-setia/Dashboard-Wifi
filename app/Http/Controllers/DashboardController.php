<?php

namespace App\Http\Controllers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        /* ---------- default ---------- */
        $onus = [];
        $connections = [];
        $totalAp = 0;
        $userOnline = 0;
        $totalUser = 0;
        $logActivity = collect();

        /* ---------- Data dari OnuApiService (native PHP, no middleware!) ---------- */
        try {
            $onuService = app(\App\Services\OnuApiService::class);

            // Get simple ONU list (cached 60s)
            $onus = $onuService->getAllOnu();
            $totalAp = count($onus);

            // Get complete data with WiFi clients (cached 5 min)
            $connections = $onuService->getAllOnuWithClients();

            $totalAp = is_array($onus) ? count($onus) : 0;

            // Hitung unique users berdasarkan MAC address untuk menghindari duplikasi
            $uniqueMacSet = [];
            if (is_array($connections)) {
                foreach ($connections as $c) {
                    if (!is_array($c) || !isset($c['wifiClients'])) {
                        continue;
                    }
                    $wifi = $c['wifiClients'];
                    $allClients = array_merge(
                        $wifi['5G'] ?? [],
                        $wifi['2_4G'] ?? [],
                        $wifi['unknown'] ?? []
                    );

                    foreach ($allClients as $client) {
                        if (!isset($client['wifi_terminal_mac']))
                            continue;
                        $mac = strtoupper(trim($client['wifi_terminal_mac']));
                        $mac = preg_replace('/[^A-F0-9:]/i', '', $mac);
                        if ($mac === '')
                            continue;
                        $uniqueMacSet[$mac] = true;
                    }
                }
            }
            $userOnline = count($uniqueMacSet);
        } catch (\Throwable $e) {
            Log::error('DashboardController@index : ' . $e->getMessage());
        }
        $totalUser = $userOnline;

        /* ---------- log activity (dummy) ---------- */
        if (is_array($connections)) {
            foreach ($connections as $c) {
                foreach ($c['wifiClients']['unknown'] ?? [] as $cl) {
                    $logActivity->push((object) [
                        'time' => now()->subMinutes(rand(1, 30)),
                        'user' => $cl['wifi_terminal_name'] ?? 'Unknown',
                        'ap' => $c['sn'] ?? null,
                        'action' => 'connected',
                    ]);
                }
            }
        }

        /* ---------- Google-Sheet sebagai sumber utama chart ---------- */
        // Build ISO date labels for the current week (Mon...Sun)
        $today = now();
        $monday = $today->copy()->startOfWeek();
        $weekDates = [];
        for ($i = 0; $i < 7; $i++) {
            $weekDates[] = $monday->copy()->addDays($i)->toDateString();
        }

        // Ambil data mingguan langsung dari DB
        $endOfWeek = $monday->copy()->endOfWeek();

        $stats = DB::table('daily_user_stats')
            ->whereBetween('date', [
                $monday->toDateString(),
                $endOfWeek->toDateString()
            ])
            ->pluck('user_count', 'date');


        $weeklyData = array_fill(0, 7, 0);
        foreach ($weekDates as $i => $dateStr) {
            $weeklyData[$i] = (int) ($stats[$dateStr] ?? 0);
        }

        // Jika minggu ini kosong, fallback ke seluruh data historis
        // if (array_sum($weeklyData) === 0) {
        //     $allStats = DB::table('daily_user_stats')
        //         ->orderBy('date')
        //         ->pluck('user_count', 'date');
        //     if (count($allStats) > 0) {
        //         $weekDates = array_keys($allStats->toArray());
        //         $weeklyData = array_values($allStats->toArray());
        //     }
        // }

        // Convert ISO date labels to Indonesian weekday names for display,
        // but keep raw dates for tooltip/context.
        $dayNames = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
        $dailyLabels = [];
        foreach ($weekDates as $d) {
            try {
                $dt = Carbon::parse($d);
                $dailyLabels[] = $dayNames[$dt->dayOfWeek] ?? $d;
            } catch (\Throwable $e) {
                $dailyLabels[] = $d;
            }
        }

        $dailyUsers = [
            'labels' => $dailyLabels,
            'raw_labels' => $weekDates,
            'data' => $weeklyData,
        ];

        /* ---------- baca Sheet paket 110 & 200 untuk mapping SN ---------- */
        $ontMap = cache()->remember(
            'ont_map_paket_all',
            86400,
            fn() => $this->readOntMap()
        );

        /* ---------- susun summary lokasi + preview clients (optimize memory) ---------- */
        $locationsMap = [];
        $globalMacSet = [];  // Track unique MAC globally to prevent double counting

        if (is_array($connections)) {
            foreach ($connections as $c) {
                $sn = strtoupper(trim($c['sn'] ?? ''));
                $info = $ontMap[$sn] ?? null;   // detail lokasi

                $clients = array_merge(
                    $c['wifiClients']['5G'] ?? [],
                    $c['wifiClients']['2_4G'] ?? [],
                    $c['wifiClients']['unknown'] ?? []
                );

                if (!isset($locationsMap[$sn])) {
                    $locationsMap[$sn] = [
                        'sn' => $sn,
                        'location' => $info['location'] ?? '-',
                        'kemantren' => $info['kemantren'] ?? '-',
                        'kelurahan' => $info['kelurahan'] ?? '-',
                        'rt' => $info['rt'] ?? '-',
                        'rw' => $info['rw'] ?? '-',
                        'clients_preview' => [],
                        'count' => 0,
                        'unique_macs' => [],  // Track unique MACs per location
                    ];
                }

                foreach ($clients as $cl) {
                    // Filter berdasarkan MAC untuk menghindari duplikasi
                    $mac = strtoupper(trim($cl['wifi_terminal_mac'] ?? ''));
                    $mac = preg_replace('/[^A-F0-9:]/i', '', $mac);

                    // Skip jika MAC kosong atau sudah tercatat di lokasi manapun
                    if ($mac === '' || isset($globalMacSet[$mac])) {
                        continue;
                    }

                    // Tandai MAC sudah tercatat
                    $globalMacSet[$mac] = true;
                    $locationsMap[$sn]['unique_macs'][$mac] = true;
                    $locationsMap[$sn]['count']++;

                    if (count($locationsMap[$sn]['clients_preview']) < 5) {
                        $locationsMap[$sn]['clients_preview'][] = [
                            'wifi_terminal_name' => $cl['wifi_terminal_name'] ?? 'Unknown',
                            'wifi_terminal_ip' => $cl['wifi_terminal_ip'] ?? '-',
                            'wifi_terminal_mac' => $cl['wifi_terminal_mac'] ?? '-',
                        ];
                    }
                }
            }
        }

        // Convert map to collection and sort by count desc
        $locationsCollection = collect(array_values($locationsMap))->sortByDesc('count')->values();

        $locPerPage = request('locPerPage', 5);
        $locPage = request('locPage', 1);
        $paginated = $locationsCollection->forPage($locPage, $locPerPage)->values()->all();

        // Prepare final locations for view: include limited clients (max 5) in key 'clients' for compatibility
        $locations = new LengthAwarePaginator(
            array_map(function ($row) {
                return [
                    'sn' => $row['sn'],
                    'location' => $row['location'],
                    'kemantren' => $row['kemantren'],
                    'kelurahan' => $row['kelurahan'],
                    'rt' => $row['rt'],
                    'rw' => $row['rw'],
                    'clients' => $row['clients_preview'] ?? [],
                    'count' => $row['count'] ?? 0,
                ];
            }, $paginated),
            $locationsCollection->count(),
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
        // Cache weekly per-day aggregated location stats for short period
        $weeklyLocationByDay = cache()->remember(sprintf('weekly_location_by_day_%s', $monday->toDateString()), 600, function () use ($monday) {
            $result = [];
            for ($i = 0; $i < 7; $i++) {
                $date = $monday->copy()->addDays($i);
                $rows = DB::table('daily_location_stats')
                    ->where('date', $date->toDateString())
                    ->groupBy('location', 'kemantren')
                    ->selectRaw('location, kemantren, SUM(user_count) as total')
                    ->get()
                    ->map(function ($r) {
                        return [
                            'location' => $r->location,
                            'kemantren' => $r->kemantren,
                            'total' => (int) $r->total,
                        ];
                    })->toArray();

                $result[$date->toDateString()] = $rows;
            }

            // If this week's aggregation is entirely empty, fallback to historical grouped data
            $allEmpty = true;
            foreach ($result as $rows) {
                if (!empty($rows)) {
                    $allEmpty = false;
                    break;
                }
            }

            if ($allEmpty) {
                Log::info('weekly_location_by_day: current week empty, falling back to historical data');

                // Build fallback: group all daily_location_stats by date -> rows
                $all = DB::table('daily_location_stats')
                    ->selectRaw('date, location, kemantren, SUM(user_count) as total')
                    ->groupBy('date', 'location', 'kemantren')
                    ->orderBy('date')
                    ->get()
                    ->groupBy('date')
                    ->map(function ($group) {
                        return $group->map(function ($r) {
                            return [
                                'location' => $r->location,
                                'kemantren' => $r->kemantren,
                                'total' => (int) $r->total,
                            ];
                        })->toArray();
                    })->toArray();

                if (!empty($all)) {
                    return $all;
                }
            }

            return $result;
        });

        // Ensure keys for the current week exist and are ordered Mon..Sun
        try {
            $expected = [];
            for ($i = 0; $i < 7; $i++) {
                $expected[] = $monday->copy()->addDays($i)->toDateString();
            }

            $ordered = [];
            foreach ($expected as $d) {
                $ordered[$d] = $weeklyLocationByDay[$d] ?? [];
            }

            $weeklyLocationByDay = $ordered;
        } catch (\Throwable $e) {
            Log::warning('failed to normalize weeklyLocationByDay keys: ' . $e->getMessage());
        }

        // Log summary to help debug empty chart issues
        try {
            $nonEmptyDays = 0;
            $totalRows = 0;
            $sample = [];
            foreach ($weeklyLocationByDay as $date => $rows) {
                if (!empty($rows)) {
                    $nonEmptyDays++;
                    $totalRows += count($rows);
                    if (empty($sample)) {
                        $sample = array_slice($rows, 0, 3);
                    }
                }
            }

            Log::info('weekly_location_by_day.summary', [
                'monday' => $monday->toDateString(),
                'non_empty_days' => $nonEmptyDays,
                'total_rows' => $totalRows,
                'sample' => $sample,
            ]);
        } catch (\Throwable $e) {
            Log::warning('failed to log weekly_location_by_day summary: ' . $e->getMessage());
        }

        // Aggregate untuk bulan ini (default current month)
        $monthlyLocationData = cache()->remember(sprintf('monthly_location_data_%d_%d', $currentYear, $currentMonth), 600, function () use ($firstDayOfMonth, $today, $currentYear, $currentMonth) {
            return DB::table('daily_location_stats')
                ->whereYear('date', $currentYear)
                ->whereMonth('date', $currentMonth)
                ->whereBetween('date', [$firstDayOfMonth, $today])
                ->groupBy('location', 'kemantren')
                ->selectRaw('location, kemantren, SUM(user_count) as total')
                ->get()
                ->map(fn($r) => [
                    'location' => $r->location,
                    'kemantren' => $r->kemantren,
                    'total' => (int) $r->total,
                ])->toArray();
        });

        // Semua bulan untuk dropdown
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];

        return view('dashboard', compact(
            'totalUser',
            'totalAp',
            'userOnline',
            'logActivity',
            'dailyUsers',
            'locations',
            'kemantrenList',
            'monthlyLocationData',
            'dayLabels',
            'weeklyLocationByDay',
            'months',
            'currentMonth',
            'currentYear'
        ));
    }

    /**
     * Return monthly aggregated location data as JSON for given month/year
     */
    public function monthlyLocationData(Request $request)
    {
        $month = (int) $request->query('month', now()->month);
        $year = (int) $request->query('year', now()->year);
        $top = (int) $request->query('top', 10);
        $kemantren = $request->query('kemantren', null);
        $search = $request->query('search', null);

        $start = Carbon::createFromDate($year, $month, 1)->toDateString();
        $end = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();

        $today = now()->toDateString();
        if ($end > $today) {
            $end = $today;
        }

        $cacheKey = sprintf('monthly_location_data_%d_%d_top%d_k%s_s%s', $year, $month, $top, $kemantren ?? 'all', $search ?? 'all');

        $data = cache()->remember($cacheKey, 300, function () use ($year, $month, $start, $end, $top, $kemantren, $search) {
            $query = DB::table('daily_location_stats')
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->whereBetween('date', [$start, $end])
                ->groupBy('location', 'kemantren')
                ->selectRaw('location, kemantren, SUM(user_count) as total');

            if ($kemantren) {
                $query->where('kemantren', $kemantren);
            }

            // allow simple search on location
            if ($search) {
                $query->havingRaw("LOWER(location) LIKE ?", ['%' . strtolower($search) . '%']);
            }

            $rows = $query->orderByDesc('total')->limit(max(1, $top))->get()
                ->map(fn($r) => [
                    'location' => $r->location,
                    'kemantren' => $r->kemantren,
                    'total' => (int) $r->total,
                ])->toArray();

            return $rows;
        });

        return response()->json($data);
    }

    /**
     * Return full clients list for a given AP SN (used by AJAX on-demand)
     */
    public function locationClients(Request $request)
    {
        $sn = strtoupper(trim($request->query('sn', '')));
        if ($sn === '') {
            return response()->json(['error' => 'sn required'], 400);
        }

        // Use OnuApiService
        $onuService = app(\App\Services\OnuApiService::class);
        $connections = $onuService->getAllOnuWithClients();

        $ontMap = cache()->remember('ont_map_paket_all', 600, fn() => $this->readOntMap());

        // Find connection by SN and build clients
        $found = null;
        foreach ($connections as $c) {
            if (strtoupper(trim($c['sn'] ?? '')) === $sn) {
                $found = $c;
                break;
            }
        }

        if (!$found) {
            return response()->json([]);
        }

        $clients = array_merge(
            $found['wifiClients']['5G'] ?? [],
            $found['wifiClients']['2_4G'] ?? [],
            $found['wifiClients']['unknown'] ?? []
        );

        $info = $ontMap[$sn] ?? [];

        $result = array_map(function ($cl) use ($found, $info) {
            return [
                'wifi_terminal_name' => $cl['wifi_terminal_name'] ?? 'Unknown',
                'wifi_terminal_ip' => $cl['wifi_terminal_ip'] ?? '-',
                'wifi_terminal_mac' => $cl['wifi_terminal_mac'] ?? '-',
                'ap_sn' => $found['sn'] ?? null,
                'ap_name' => $info['location'] ?? '-',
                'ap_kemantren' => $info['kemantren'] ?? '-',
            ];
        }, $clients);

        return response()->json(array_values($result));
    }

    /* ---------- Mapping SN -> lokasi (pakai CSV lokal ACSfiks.csv) ---------- */
    private function readOntMap(): array
    {
        $path = public_path('storage/ACSfiks.csv');
        if (!is_file($path)) {
            Log::error('readOntMap: ACSfiks.csv not found at ' . $path);
            return [];
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            Log::error('readOntMap: unable to open ' . $path);
            return [];
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            return [];
        }

        $indexes = [];
        foreach ($header as $idx => $name) {
            $key = strtolower(trim(preg_replace('/\s+/', '_', $name)));
            $indexes[$key] = $idx;
        }

        $map = [];
        $get = function (array $row, string $key) use ($indexes): string {
            $idx = $indexes[$key] ?? null;
            return $idx === null ? '' : trim($row[$idx] ?? '');
        };

        while (($row = fgetcsv($handle)) !== false) {
            $sn = strtoupper($get($row, 'sn'));
            if ($sn === '') {
                continue;
            }

            $map[$sn] = [
                'location' => $get($row, 'nama_lokasi'),
                'kemantren' => $get($row, 'kemantren'),
                'kelurahan' => $get($row, 'kelurahan'),
                'rt' => $get($row, 'rt'),
                'rw' => $get($row, 'rw'),
                'ip' => $get($row, 'ip'),
                'pic' => $get($row, 'pic'),
                'coordinate' => $get($row, 'titik_koordinat'),
            ];
        }

        fclose($handle);

        return $map;
    }

    /**
     * Return monthly user stats data as JSON for given month/year
     */
    public function monthlyUserData(Request $request)
    {
        $month = (int) $request->query('month', now()->month);
        $year = (int) $request->query('year', now()->year);

        $start = Carbon::createFromDate($year, $month, 1)->toDateString();
        $end = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();

        $today = now()->toDateString();
        if ($end > $today) {
            $end = $today;
        }

        $cacheKey = sprintf('monthly_user_data_%d_%d', $year, $month);

        $data = cache()->remember($cacheKey, 300, function () use ($start, $end) {
            $stats = DB::table('daily_user_stats')
                ->whereBetween('date', [$start, $end])
                ->orderBy('date')
                ->get();

            $labels = [];
            $values = [];

            foreach ($stats as $stat) {
                $labels[] = $stat->date;
                $values[] = (int) $stat->user_count;
            }

            return [
                'labels' => $labels,
                'data' => $values,
            ];
        });

        return response()->json($data);
    }
}
