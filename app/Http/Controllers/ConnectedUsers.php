<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

class ConnectedUsers extends Controller
{
    public function index(): Response
    {
        try {
            $aps = Http::timeout(10)->get(config('services.onu_api.url'))->json();

            foreach ($aps as &$ap) {
                $clients = $ap['wifiClients'] ?? [];

                $count5g = isset($clients['5G']) ? count($clients['5G']) : 0;
                $count24 = isset($clients['2_4G']) ? count($clients['2_4G']) : 0;
                $countUnknown = isset($clients['unknown']) ? count($clients['unknown']) : 0;

                $ap['connected'] = $count5g + $count24 + $countUnknown;
            }

            return response()->view('connectedUsers.connectUsers', compact('aps'));

        } catch (\Exception $e) {
            return response()->view('connectedUsers.connectUsers', [
                'aps' => [],
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
