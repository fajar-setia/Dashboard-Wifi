<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class AlertNotifController extends Controller
{
    public static function getAlertCount()
    {
        try {
            $url = config('services.onu_api.url');

            $response = Http::timeout(5)->get($url);

            if (!$response->successful()) {
                return 0;
            }

            $data = $response->json();

            // Pastikan $data array/list
            if (!is_array($data)) {
                return 0;
            }

            // Hitung yang state = offline
            $offlineCount = collect($data)
                ->where('state', 'offline')
                ->count();

            return $offlineCount;

        } catch (\Throwable $th) {
            return 0;
        }
    }
}

