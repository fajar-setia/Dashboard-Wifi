<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;

class AccessPointController extends Controller
{
    public function index(Request $request)
    {
        try {
            $responses = Http::pool(fn ($pool) => [
                $pool->timeout(5)->get('http://172.16.105.26:6767/api/onu'),
                $pool->timeout(5)->get('http://172.16.105.26:6767/api/onu/connect'),
            ]);

            $resp0Ok = is_object($responses[0]) && method_exists($responses[0], 'successful') && $responses[0]->successful();
            $resp1Ok = is_object($responses[1]) && method_exists($responses[1], 'successful') && $responses[1]->successful();

            if (! $resp0Ok || ! $resp1Ok) {
                $perPage = $request->get('perPage', 10);
                $page = $request->get('page', 1);

                $emptyPaginator = new LengthAwarePaginator(
                    [],
                    0,
                    $perPage,
                    $page,
                    [
                        'path' => $request->url(),
                        'query' => $request->query(),
                    ]
                );

                return view('accessPoint.accessPoint', [
                    'devices' => $emptyPaginator,
                    'error' => 'Gagal mengambil data dari API',
                    'search' => $request->get('search'),
                ]);
            }
        } catch (ConnectionException) {
            $perPage = $request->get('perPage', 10);
            $page = $request->get('page', 1);

            $emptyPaginator = new LengthAwarePaginator(
                [],
                0,
                $perPage,
                $page,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );

            return view('accessPoint.accessPoint', [
                'devices' => $emptyPaginator,
                'error' => 'Koneksi ke API timeout',
                'search' => $request->get('search'),
            ]);
        }

        $onuData = $responses[0]->json();
        $connectData = collect($responses[1]->json())->keyBy('sn');

        /* ================= MERGE ================= */
        $devices = collect($onuData)->map(function ($onu) use ($connectData) {
            $connect = $connectData->get($onu['sn']);

            return [
                'sn' => $onu['sn'] ?? null,
                'model' => $onu['model'] ?? null,
                'state' => strtolower($connect['state'] ?? $onu['state'] ?? 'offline'),
                'wifiClients' => $connect['wifiClients'] ?? [
                    '5G' => [],
                    '2_4G' => [],
                    'unknown' => [],
                ],
            ];
        });

        /* =======================
     | SEARCH
     ======================= */
        $search = $request->get('search');
        if ($search) {
            $devices = $devices->filter(fn ($d) => str_contains(strtolower($d['sn']), strtolower($search)) ||
                str_contains(strtolower($d['model']), strtolower($search))
            );
        }

        /* =======================
         | PAGINATION
         ======================= */
        $perPage = $request->get('perPage', 10);
        $page = $request->get('page', 1);

        $devices = new LengthAwarePaginator(
            $devices->forPage($page, $perPage),
            $devices->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );

        return view('accessPoint.accessPoint', [
            'devices' => $devices,
            'error' => null,
            'search' => $search,
        ]);
    }
}
