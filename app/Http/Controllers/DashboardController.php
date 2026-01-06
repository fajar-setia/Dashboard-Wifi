<?php

namespace App\Http\Controllers;

use Google\Client as GoogleClient;
use Google\Service\Sheets;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        // ================= DEFAULT =================
        $onus = [];
        $connections = [];
        $totalAp = 0;
        $userOnline = 0;
        $totalUser = 0;
        $logActivity = collect();

        // ================= API ONU =================
        try {
            $responseOnu = Http::timeout(5)->get('http://172.16.100.26:67/api/onu');
            if ($responseOnu->ok()) {
                $onus = $responseOnu->json();
                $totalAp = is_array($onus) ? count($onus) : 0;
            }

            $responseConn = Http::timeout(5)->get('http://172.16.100.26:67/api/onu/connect');
            if ($responseConn->ok()) {
                $connections = $responseConn->json();

                foreach ($connections as $c) {
                    $wifi = $c['wifiClients'] ?? [];
                    $userOnline += count($wifi['5G'] ?? []);
                    $userOnline += count($wifi['2_4G'] ?? []);
                    $userOnline += count($wifi['unknown'] ?? []);
                }
            }
        } catch (\Throwable $e) {
            Log::error('DashboardController@index API error: '.$e->getMessage());
        }

        $totalUser = $userOnline;

        // ================= LOG ACTIVITY =================
        foreach ($connections as $c) {
            foreach ($c['wifiClients']['unknown'] ?? [] as $client) {
                $logActivity->push((object) [
                    'time' => now()->subMinutes(rand(1, 30)),
                    'user' => $client['wifi_terminal_name'] ?? 'Unknown',
                    'ap' => $c['sn'] ?? null,
                    'action' => 'connected',
                ]);
            }
        }

<<<<<<< Updated upstream
        // Mulai dari Senin minggu ini, tampilkan sampai hari ini, sisanya kosong sampai Minggu
        $today = now();
        $dayOfWeek = $today->dayOfWeek; // 0=Sunday, 1=Monday, ..., 6=Saturday
        
        // Hitung: Senin minggu ini adalah hari berapa
        $daysToMonday = ($dayOfWeek == 0) ? 1 : ($dayOfWeek == 1 ? 0 : $dayOfWeek - 1);
        $mondayThisWeek = $today->copy()->subDays($daysToMonday);
        
        // Build array: Senin (hari 0) sampai hari ini, sisa hari minggu kosong (0)
        $base = collect();
        for ($i = 0; $i < 7; $i++) {
            $date = $mondayThisWeek->copy()->addDays($i)->toDateString();
            $base[$date] = 0; // default 0
        }

        // timpa dengan data yang ADA (hanya untuk Senin sampai hari ini)
        $stats = DB::table('daily_user_stats')
            ->where('date', '>=', $mondayThisWeek->toDateString())
            ->where('date', '<=', $today->toDateString())
            ->pluck('user_count', 'date');

        $base = $base->merge($stats); // hari ada → ditimpa

        $labels = $base->keys();  // kirim tanggal YYYY-MM-DD
        $data = $base->values();
=======
        // ================= CHART MINGGUAN (DB – STABIL) =================
        $dayLabels = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

        $today = now();
        $dayOfWeek = $today->dayOfWeek; // 0=Min, 1=Sen
        $daysToMonday = ($dayOfWeek === 0) ? 6 : $dayOfWeek - 1;
        $monday = $today->copy()->subDays($daysToMonday);

        // init base 7 hari
        $base = collect();
        for ($i = 0; $i < 7; $i++) {
            $base[$monday->copy()->addDays($i)->toDateString()] = 0;
        }

        // ambil dari DB
        $stats = DB::table('daily_user_stats')
            ->whereBetween('date', [
                $monday->toDateString(),
                $monday->copy()->addDays(6)->toDateString(),
            ])
            ->pluck('user_count', 'date');

        // merge aman
        $base = $base->merge($stats);
>>>>>>> Stashed changes

        // FINAL chart data (LABEL HARI)
        $dailyUsers = [
            'labels' => $dayLabels,
            'data'   => $base->values()->values(),
        ];

        // ================= GOOGLE SHEET (TAMBAHAN DATA) =================
        $sheetUsers = cache()->remember(
            'sheet_users_location',
            300,
            fn () => $this->readSheet()
        );

        // ================= PAGINATION CLIENT =================
        $allClients = collect();

        foreach ($connections as $c) {
            $wifi = $c['wifiClients'] ?? [];
            $clients = array_merge(
                $wifi['5G'] ?? [],
                $wifi['2_4G'] ?? [],
                $wifi['unknown'] ?? []
            );

            foreach ($clients as $client) {
                $client['ap_sn'] = $c['sn'] ?? null;
                $client['ap_name'] = $c['name'] ?? null;
                $allClients->push($client);
            }
        }

        $perPage = request('perPage', 10);
        $page = request('page', 1);

        $clients = new LengthAwarePaginator(
            $allClients->forPage($page, $perPage),
            $allClients->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );

        // ================= RETURN VIEW =================
        return view('dashboard', [
            'totalUser'  => $totalUser,
            'totalAp'    => $totalAp,
            'userOnline' => $userOnline,
            'logActivity'=> $logActivity,
            'clients'    => $clients,
            'dailyUsers' => $dailyUsers,
            'sheetUsers' => $sheetUsers, // tambahan (lokasi / mapping nanti)
        ]);
    }

    // ================= GOOGLE CLIENT =================
    private function getClient(): GoogleClient
    {
        $client = new GoogleClient();
        $client->setApplicationName('Laravel Dashboard');
        $client->setScopes([Sheets::SPREADSHEETS_READONLY]);
        $client->setAuthConfig(config_path('google/service-accounts.json'));

        return $client;
    }

    // ================= READ GOOGLE SHEET =================
    private function readSheet(): array
    {
        $client = $this->getClient();
        $service = new Sheets($client);

        $spreadsheetId = config('services.google.sheet_id');
        $range = 'Rapi1!B4:B15';

        $response = $service->spreadsheets_values->get($spreadsheetId, $range);

        return $response->getValues() ?? [];
    }
}
