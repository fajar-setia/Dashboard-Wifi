<?php

namespace App\Http\Controllers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class AlertController extends Controller
{
    public function index()
    {
        try {
            $response = Http::timeout(5)->get(config('services.onu_api.url'));

            if (! $response->successful()) {
                $aps = collect();
            } else {
                $aps = collect($response->json() ?: []);
            }

            // Pastikan selalu Collection (bukan null / array / object)
            $aps = collect($aps ?: []);
            // contoh pengolahan singkat
            $apOffline = $aps->where('state', 'offline')->values();

            $newDevices = $aps->flatMap(function ($ap) {
                $clients = $ap['wifiClients'] ?? [];

                return collect($clients)->flatMap(fn ($group) => collect($group)->pluck('wifi_terminal_mac'));
            })->unique()->take(5)->values();

            $activeUsers = $aps->sum(function ($ap) {
                $clients = $ap['wifiClients'] ?? [];

                return collect($clients)->sum(fn ($g) => is_countable($g) ? count($g) : 0);
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
