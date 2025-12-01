<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AccessPointController extends Controller
{
    public function index()
    {
        // Ambil data ONU
        $onuResponse = Http::timeout(10)->get('http://172.16.100.26:67/api/onu');
        $connectResponse = Http::timeout(10)->get('http://172.16.100.26:67/api/onu/connect');

        if (!$onuResponse->successful() || !$connectResponse->successful()) {
            return view('accessPoint.accessPoint', [
                'devices' => collect([]),
                'error' => 'Gagal mengambil data dari API'
            ]);
        }

        $onuData = $onuResponse->json();
        $connectRaw = $connectResponse->json();

        // Sesuaikan format
        $connectData = is_array($connectRaw) && isset($connectRaw['data'])
            ? $connectRaw['data']
            : $connectRaw;

        $connectData = collect($connectData);

        // Gabungkan data berdasarkan SN
        $merged = collect($onuData)->map(function ($onu) use ($connectData) {
            $connect = $connectData->firstWhere('sn', $onu['sn']);

            return [
                'sn' => $onu['sn'] ?? null,
                'model' => $onu['model'] ?? null,
                'state' => $connect['state'] ?? $onu['state'] ?? 'offline',
                'wifiClients' => $connect['wifiClients'] ?? [
                    '5G' => [],
                    '2_4G' => [],
                    'unknown' => []
                ],
            ];
        });

        return view('accessPoint.accessPoint', [
            'devices' => $merged,
            'error' => null
        ]);
    }
}
