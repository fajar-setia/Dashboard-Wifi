<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Collection;

class AccessPointController extends Controller
{
    /* =========================
     | UTILS
     ========================= */

    private function normalizeSn(?string $sn): ?string
    {
        if (!$sn)
            return null;

        $sn = trim($sn);
        if (str_contains($sn, '-')) {
            $sn = trim(substr($sn, strrpos($sn, '-') + 1));
        }

        return $sn;
    }

    private function loadOntLocations(): Collection
    {
        $paths = [
            storage_path('app/public/ACSfiks.csv'),
            storage_path('app/ACSfiks.csv'),
            public_path('storage/ACSfiks.csv'),
            base_path('storage/ACSfiks.csv'),
        ];

        $file = collect($paths)->first(fn($p) => file_exists($p));
        if (!$file)
            return collect();

        $lines = array_filter(array_map('trim', file($file)));
        if (count($lines) < 2)
            return collect();

        $header = array_map(
            fn($h) => strtolower(str_replace(' ', '_', trim($h))),
            str_getcsv(array_shift($lines))
        );

        $headerCount = count($header);

        return collect($lines)
            ->map(function ($line) use ($header, $headerCount) {
                $row = str_getcsv($line);

                // 🔥 INI KUNCI NYA
                if (count($row) < $headerCount) {
                    $row = array_pad($row, $headerCount, null);
                } elseif (count($row) > $headerCount) {
                    $row = array_slice($row, 0, $headerCount);
                }

                return array_combine($header, $row);
            })
            ->filter(fn($r) => !empty($r['sn']))
            ->mapWithKeys(function ($r) {
                $sn = $this->normalizeSn($r['sn'] ?? null);

                return [
                    $sn => [
                        'lokasi' => $r['lokasi'] ?? null,
                        'kelurahan' => $r['kelurahan'] ?? null,
                        'kemantren' => $r['kemantren'] ?? null,
                    ]
                ];
            });
    }


    /* =========================
     | MAIN
     ========================= */

    public function index(Request $request)
    {
        try {
            /* ========= Use OnuApiService (native PHP!) ========= */
            $onuService = app(\App\Services\OnuApiService::class);

            $onuData = $onuService->getAllOnu();
            $connections = $onuService->getAllOnuWithClients();

            // Map connections by SN for easy lookup
            $connectData = collect($connections)->mapWithKeys(fn($i) => [
                $this->normalizeSn($i['sn'] ?? null) => $i
            ]);
        } catch (ConnectionException) {
            return $this->emptyView($request, 'Koneksi ke API timeout');
        }

        if (!is_array($onuData)) {
            return $this->emptyView($request, 'Data API tidak valid');
        }

        /* ========= CACHE CSV ========= */
        $locationData = Cache::remember(
            'ont_locations',
            now()->addDay(),
            fn() => $this->loadOntLocations()
        );

        /* ========= MERGE DATA ========= */
        $devices = collect($onuData)->map(function ($onu) use ($connectData, $locationData) {
            $sn = $this->normalizeSn($onu['sn'] ?? null);
            $connect = $connectData->get($sn, []);
            $location = $locationData->get($sn, []);

            $userCount =
                count($connect['wifiClients']['5G'] ?? []) +
                count($connect['wifiClients']['2_4G'] ?? []) +
                count($connect['wifiClients']['unknown'] ?? []);

            return [
                'sn' => $sn,
                'model' => $onu['model'] ?? '-',
                'state' => strtolower($connect['state'] ?? $onu['state'] ?? 'offline'),
                'lokasi' => collect([
                    $location['lokasi'] ?? null,
                    $location['kelurahan'] ?? null,
                    $location['kemantren'] ?? null,
                ])->filter()->implode('-') ?: 'Lokasi Tidak Diketahui',
                'wifi_user_count' => $userCount,
                'wifiClients' => $connect['wifiClients'] ?? [],
            ];
        });

        /* ========= SEARCH ========= */
        if ($search = $request->get('search')) {
            $devices = $devices->filter(
                fn($d) =>
                str_contains(strtolower($d['sn']), strtolower($search)) ||
                str_contains(strtolower($d['model']), strtolower($search))
            );
        }

        /* ========= SUMMARY (SEBELUM PAGINATION) ========= */
        $summary = [
            'total' => $devices->count(),
            'online' => $devices->where('state', 'online')->count(),
            'offline' => $devices->where('state', '!=', 'online')->count(),
            'users' => $devices->sum('wifi_user_count'),
        ];

        /* ========= PAGINATION ========= */
        $perPage = (int) $request->get('perPage', 10);
        $page = (int) $request->get('page', 1);

        $paginated = new LengthAwarePaginator(
            $devices->forPage($page, $perPage)->values(),
            $devices->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('accessPoint.accessPoint', [
            'devices' => $paginated,
            'summary' => $summary,
            'error' => null,
            'search' => $search,
        ]);
    }

    /* =========================
     | EMPTY VIEW
     ========================= */

    private function emptyView(Request $request, string $error)
    {
        return view('accessPoint.accessPoint', [
            'devices' => new LengthAwarePaginator([], 0, 10),
            'summary' => [
                'total' => 0,
                'online' => 0,
                'offline' => 0,
                'users' => 0,
            ],
            'error' => $error,
            'search' => $request->get('search'),
        ]);
    }
}
