<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Collection;

class AlertController extends Controller
{
    public function index()
    {
        try {
            $raw = Http::timeout(10)
                ->get(config('services.onu_api.url'))
                ->json();

            // Pastikan selalu Collection (bukan null / array / object)
            $aps = collect($raw ?: []);

            // contoh pengolahan singkat
            $apOffline = $aps->where('state', 'offline')->values();

            $newDevices = $aps->flatMap(function ($ap) {
                $clients = $ap['wifiClients'] ?? [];
                return collect($clients)->flatMap(fn($group) => collect($group)->pluck('wifi_terminal_mac'));
            })->unique()->take(10)->values();

            $activeUsers = $aps->sum(function ($ap) {
                $clients = $ap['wifiClients'] ?? [];
                return collect($clients)->sum(fn($g) => is_countable($g) ? count($g) : 0);
            });

            $alertCount = $apOffline->count();

            return view('Alert.alert', [
                'aps' => $aps,
                'apOffline' => $apOffline,
                'deviceCount' => $newDevices,
                'activeUsers' => $activeUsers,
                'alertCount' => $alertCount,
            ]);
        } catch (\Throwable $e) {
            return view('Alert.alert', [
                'aps' => collect(),
                'apOffline' => collect(),
                'deviceCount' => collect(),
                'activeUsers' => 0,
                'alertCount' => 0,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
