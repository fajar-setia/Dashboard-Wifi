<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class ConnectedUsers extends Controller
{
    public function index()
    {
        try {
            $responses = Http::pool(fn ($pool) => [
                $pool->timeout(10)->get(config('services.onu_api.url')),
            ]);

            $response = $responses[0];

            // ❌ HTTP error tapi koneksi berhasil
            if (! $response->successful()) {

                $status = $response->status();

                if (in_array($status, [500, 502, 503, 504])) {
                    $error = 'API bermasalah / tidak merespon';
                } else {
                    $error = 'Gagal mengambil data dari API';
                }

                return view('connectedUsers.connectUsers', [
                    'aps' => [],
                    'error' => $error,
                ]);
            }

            /* ==========================================
             | 3️⃣ NORMAL FLOW
             ========================================== */
            $aps = $response->json();

            foreach ($aps as &$ap) {
                $clients = $ap['wifiClients'] ?? [];

                $count5g = isset($clients['5G']) ? count($clients['5G']) : 0;
                $count24 = isset($clients['2_4G']) ? count($clients['2_4G']) : 0;
                $countUnknown = isset($clients['unknown']) ? count($clients['unknown']) : 0;

                $ap['connected'] = $count5g + $count24 + $countUnknown;
            }

            return view('connectedUsers.connectUsers', compact('aps'));

        } catch (ConnectionException $e) {
            return view('connectedUsers.connectUsers', [
                'aps' => [],
                'error' => 'Koneksi ke API timeout',
            ]);
        }
    }
}
