<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;

class AccessPointController extends Controller
{
    private function normalizeSn(?string $sn): ?string
    {
        if (! $sn) {
            return null;
        }

        $sn = trim($sn);

        if (str_contains($sn, '-')) {
            $sn = trim(substr($sn, strrpos($sn, '-') + 1));
        }

        return $sn;
    }

    private function loadOntLocations(): \Illuminate\Support\Collection
    {
        $candidates = [
            storage_path('app/public/ACSfiks.csv'),
            storage_path('app/ACSfiks.csv'),
            public_path('storage/ACSfiks.csv'),
            base_path('public/storage/ACSfiks.csv'),
            base_path('storage/ACSfiks.csv'),
        ];

        $filePath = null;
        foreach ($candidates as $p) {
            if (file_exists($p)) {
                $filePath = $p;
                break;
            }
        }

        if (! $filePath) {
            return collect();
        }

        $lines = array_filter(array_map('trim', explode("\n", file_get_contents($filePath))));

        if (count($lines) < 2) {
            return collect();
        }

        // Normalize header keys: trim, lowercase and replace spaces with underscores
        $header = array_map(fn($h) => strtolower(trim(preg_replace('/\s+/', '_', $h))), str_getcsv(array_shift($lines)));
        $headerCount = count($header);

        return collect($lines)
            ->map(function ($line) use ($header, $headerCount) {
                $row = str_getcsv($line);

                if (count($row) < $headerCount) {
                    $row = array_pad($row, $headerCount, null);
                }

                if (count($row) > $headerCount) {
                    $row = array_slice($row, 0, $headerCount);
                }

                return array_combine($header, $row);
            })
            ->filter(fn ($row) => !empty($row['sn']))
            ->mapWithKeys(function ($row) {
                $sn = $this->normalizeSn($row['sn'] ?? null);

                return [
                    $sn => [
                        'lokasi' => $row['lokasi'] ?? null,
                        'kemantren' => $row['kemantren'] ?? null,
                        'kelurahan' => $row['kelurahan'] ?? null,
                    ]
                ];
            });
    }

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
        $connectData = collect($responses[1]->json())
            ->mapWithKeys(fn ($item) => [
                $this->normalizeSn($item['sn'] ?? null) => $item
            ]);
            
        /* ================= MERGE ================= */
        $locationData = $this -> loadOntLocations();

        $devices = collect($onuData)->map(function ($onu) use ($locationData, $connectData) {

        $sn = $this->normalizeSn($onu['sn'] ?? null);

        $connect = $connectData->get($sn);
        $location = $locationData->get($sn, []);

        return [
            'sn' => $sn,
            'model' => $onu['model'] ?? null,
            'state' => strtolower($connect['state'] ?? $onu['state'] ?? 'offline'),
            'lokasi' => (function ($location) {
                    $parts = array_filter([
                        $location['lokasi'] ?? null,
                        $location['kelurahan'] ?? null,
                        $location['kemantren'] ?? null,
                    ], fn($v) => $v !== null && $v !== '');

                    return count($parts) ? implode(' - ', $parts) : '-';
                })($location),

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
