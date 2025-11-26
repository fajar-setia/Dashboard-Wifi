<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        // default
        $onus = [];
        $connections = [];
        $totalAp = 0;
        $userOnline = 0;
        $totalUser = 0;
        $logActivity = collect();
        $dailyUsers = [
            'labels' => ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
            'data' => [12, 15, 9, 20, 25, 18, 10],
        ];

        try {
            $responseOnu = Http::timeout(5)->get('http://172.16.100.26:67/api/onu');
            if ($responseOnu->ok()) {
                $onus = $responseOnu->json();
                $totalAp = is_array($onus) ? count($onus) : 0;
            }

            $responseConn = Http::timeout(5)->get('http://172.16.100.26:67/api/onu/connect');
            if ($responseConn->ok()) {
                $connections = $responseConn->json();
                if (is_array($connections)) {
                    foreach ($connections as $c) {
                        if (! is_array($c) || ! isset($c['wifiClients']) || ! is_array($c['wifiClients'])) {
                            continue;
                        }
                        $wifi = $c['wifiClients'];
                        $userOnline += isset($wifi['5G']) ? count($wifi['5G']) : 0;
                        $userOnline += isset($wifi['2_4G']) ? count($wifi['2_4G']) : 0;
                        $userOnline += isset($wifi['unknown']) ? count($wifi['unknown']) : 0;
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('DashboardController@index error: '.$e->getMessage());
            // keep defaults so view still renders
        }

        $totalUser = $userOnline;

        // simple logActivity from connections
        if (is_array($connections)) {
            foreach ($connections as $c) {
                $sn = $c['sn'] ?? null;
                $wifi = $c['wifiClients'] ?? [];
                foreach ($wifi['unknown'] ?? [] as $client) {
                    $logActivity->push((object) [
                        'time' => now()->subMinutes(rand(1, 30)),
                        'user' => $client['wifi_terminal_name'] ?? 'Unknown',
                        'ap' => $sn,
                        'action' => 'connected',
                    ]);
                }
            }
        }

        return view('dashboard', [
            'totalUser' => $totalUser,
            'totalAp' => $totalAp,
            'userOnline' => $userOnline,
            'logActivity' => $logActivity,
            'connections' => $connections,
            'dailyUsers' => $dailyUsers,
        ]);
    }
}
