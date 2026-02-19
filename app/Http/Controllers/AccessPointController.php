<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
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

        $sn = strtoupper(preg_replace('/\s+/', '', $sn));

        return $sn;
    }

    /**
     * Baca CSV dan index by IP.
     * CSV hanya dipakai untuk mencari lokasi (nama_lokasi, kemantren, kelurahan, rt, rw).
     * Semua data lain (sn, model, ip, state) diambil dari API.
     */
    private function readLocationMapByIp(): Collection
    {
        $paths = [
            storage_path('app/public/ACSfiks.csv'),
            public_path('storage/ACSfiks.csv'),
            storage_path('app/ACSfiks.csv'),
            base_path('storage/ACSfiks.csv'),
        ];
        $file = collect($paths)->first(fn($p) => file_exists($p));
        if (!$file) return collect();

        $lines = array_filter(array_map('trim', file($file)));
        if (count($lines) < 2) return collect();

        $header = array_map(
            fn($h) => strtolower(str_replace(' ', '_', trim($h))),
            str_getcsv(array_shift($lines))
        );
        $headerCount = count($header);

        return collect($lines)
            ->map(function ($line) use ($header, $headerCount) {
                $row = str_getcsv($line);
                if (count($row) < $headerCount) {
                    $row = array_pad($row, $headerCount, null);
                } elseif (count($row) > $headerCount) {
                    $row = array_slice($row, 0, $headerCount);
                }
                return array_combine($header, $row);
            })
            ->filter(fn($r) => !empty($r['ip']))
            ->mapWithKeys(fn($r) => [
                trim($r['ip']) => [
                    'location'     => $r['nama_lokasi'] ?? null,
                    'kemantren'    => $r['kemantren']   ?? null,
                    'kelurahan'    => $r['kelurahan']   ?? null,
                    'rt'           => $r['rt']          ?? null,
                    'rw'           => $r['rw']          ?? null,
                    'id_lifemedia' => $r['id_lifemedia'] ?? null,
                ],
            ]);
    }


    /* =========================
     | MAIN
     ========================= */

    public function index(Request $request)
    {
        try {
            $onuService = app(\App\Services\OnuApiService::class);

            $onuData = $onuService->getAllOnu();
            $connections = $onuService->getAllOnuWithClients();

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
        // CSV di-index by IP — hanya untuk lookup lokasi
        $locationByIp = Cache::remember(
            'ont_location_by_ip',
            86400,
            fn() => $this->readLocationMapByIp()
        );

        /* ========= MERGE DATA ========= */
        $devices = collect($onuData)->map(function ($onu) use ($connectData, $locationByIp) {
            $sn      = $this->normalizeSn($onu['sn'] ?? null);
            $connect = $connectData->get($sn, []);

            // IP dari API sebagai primary key untuk lookup lokasi
            $apiIp   = trim($onu['ip'] ?? '');
            $location = $apiIp ? $locationByIp->get($apiIp) : null;

            $userCount =
                count($connect['wifiClients']['5G'] ?? []) +
                count($connect['wifiClients']['2_4G'] ?? []) +
                count($connect['wifiClients']['unknown'] ?? []);

            return [
                // Semua data dari API
                'sn'           => $sn,
                'model'        => $onu['model'] ?? '-',
                'state'        => strtolower($connect['state'] ?? $onu['state'] ?? 'offline'),
                'ip'           => $apiIp ?: '-',
                // Lokasi dari CSV (matched by IP), fallback jika tidak ketemu
                'location'     => $location['location']     ?? 'Lokasi Tidak Diketahui',
                'kemantren'    => $location['kemantren']     ?? '-',
                'kelurahan'    => $location['kelurahan']     ?? '-',
                'rt'           => (string) ($location['rt'] ?? ''),
                'rw'           => (string) ($location['rw'] ?? ''),
                'id_lifemedia' => $location['id_lifemedia']  ?? '-',
                'location_found' => $location !== null,
                'wifi_user_count' => $userCount,
                'wifiClients'  => $connect['wifiClients'] ?? [],
            ];
        });

        /* ========= SEARCH ========= */
        if ($search = $request->get('search')) {
            $terms = preg_split('/\s+/', strtolower(trim($search)));
            $devices = $devices->filter(function ($d) use ($terms) {
                $hay = strtolower(implode(' ', [
                    $d['sn'] ?? '',
                    $d['model'] ?? '',
                    $d['location'] ?? '',
                    $d['kemantren'] ?? '',
                    $d['kelurahan'] ?? '',
                    $d['ip'] ?? '',
                    $d['id_lifemedia'] ?? '',
                ]));
                foreach ($terms as $term) {
                    if ($term !== '' && !str_contains($hay, $term)) return false;
                }
                return true;
            });
        }

        /* ========= FILTER BY STATE ========= */
        if ($stateFilter = $request->get('state')) {
            $devices = $devices->filter(fn($d) => $d['state'] === strtolower($stateFilter));
        }

        /* ========= SUMMARY ========= */
        $summary = [
            'total'            => $devices->count(),
            'online'           => $devices->where('state', 'online')->count(),
            'offline'          => $devices->where('state', '!=', 'online')->count(),
            'users'            => $devices->sum('wifi_user_count'),
            'lokasi_ditemukan' => $devices->where('location_found', true)->count(),
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
                'lokasi_ditemukan' => 0,
            ],
            'error' => $error,
            'search' => $request->get('search'),
        ]);
    }
}
