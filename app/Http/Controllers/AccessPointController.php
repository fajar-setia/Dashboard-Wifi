<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class AccessPointController extends Controller
{
    public function index()
    {
        $responses = Http::pool(fn ($pool) => [
            $pool->timeout(5)->get('http://172.16.100.26:67/api/onu'),
            $pool->timeout(5)->get('http://172.16.100.26:67/api/onu/connect'),
        ]);

        $onuResponse = $responses[0];
        $connectResponse = $responses[1];

        if (! $onuResponse->successful() || ! $connectResponse->successful()) {
            return view('accessPoint.accessPoint', [
                'devices' => collect([]),
                'error' => 'Gagal mengambil data dari API',
            ]);
        }

        $onuData = $onuResponse->json();
        $connectRaw = $connectResponse->json();

        $connectData = is_array($connectRaw) && isset($connectRaw['data'])
            ? $connectRaw['data']
            : $connectRaw;

        $connectIndexed = collect($connectData)->keyBy('sn');

        $merged = collect($onuData)->map(function ($onu) use ($connectIndexed) {
            $connect = $connectIndexed->get($onu['sn']);

            return [
                'sn' => $onu['sn'] ?? null,
                'model' => $onu['model'] ?? null,
                'state' => $connect['state'] ?? $onu['state'] ?? 'offline',
                'wifiClients' => $connect['wifiClients'] ?? [
                    '5G' => [],
                    '2_4G' => [],
                    'unknown' => [],
                ],
            ];
        });

        return view('accessPoint.accessPoint', [
            'devices' => $merged,
            'error' => null,
        ]);
    }
}
