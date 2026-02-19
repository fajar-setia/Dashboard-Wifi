<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Services\OnuApiService;

class ConnectedUsers extends Controller
{
    /**
     * Baca CSV dan index by IP.
     * CSV hanya untuk lokasi (nama_lokasi, kemantren, kelurahan, rt, rw, id_lifemedia).
     * Semua data lain dari API.
     */
    private function readLocationMapByIp()
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
                    'location'     => $r['nama_lokasi']  ?? null,
                    'kemantren'    => $r['kemantren']    ?? null,
                    'kelurahan'    => $r['kelurahan']    ?? null,
                    'rt'           => $r['rt']           ?? null,
                    'rw'           => $r['rw']           ?? null,
                    'id_lifemedia' => $r['id_lifemedia'] ?? null,
                ],
            ]);
    }
    public function index(Request $request)
    {
        try {
            // Gunakan OnuApiService untuk mendapatkan data yang sudah terstruktur
            $onuService = app(OnuApiService::class);
            $raw = $onuService->getAllOnuWithClients();

            // Validasi bahwa $raw adalah array
            if (!is_array($raw)) {
                \Log::warning('ConnectedUsers: API response bukan array', [
                    'type' => gettype($raw),
                ]);
                $perPage = $request->get('perPage', 5);
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

                return view('connectedUsers.connectUsers', [
                    'aps' => $emptyPaginator,
                    'error' => 'Format api nya ndak valid lek',
                ]);
            }

            // Process API JSON without collecting everything into memory. mungkin karena terlalu raw jadi lebih sulit di parse 
            // $raw = $response->json();

            $search = $request->get('search');
            $perPage = (int) $request->get('perPage', 5);
            $page = (int) $request->get('page', 1);

            // Load lokasi dari CSV, di-index by IP
            $locationByIp = Cache::remember(
                'ont_location_by_ip',
                86400,
                fn() => $this->readLocationMapByIp()
            );

            $start = max(0, ($page - 1) * $perPage);
            $collected = 0;
            $pageItems = [];

            foreach ($raw as $ap) {
                $clients = $ap['wifiClients'] ?? [];

                // SN dan IP dari API
                $rawSn = (string) ($ap['sn'] ?? '');
                $sn    = $this->normalizeSn($rawSn);
                $apiIp = trim($ap['ip'] ?? '');

                // Lookup lokasi dari CSV by IP dari API
                $location = $apiIp ? $locationByIp->get($apiIp) : null;

                // Semua data dari API, lokasi dari CSV
                $ap['sn']          = $sn ?: $rawSn;
                $ap['ip']          = $apiIp ?: '-';
                $ap['location']    = $location['location']     ?? 'Lokasi Tidak Diketahui';
                $ap['kemantren']   = $location['kemantren']    ?? '-';
                $ap['kelurahan']   = $location['kelurahan']    ?? '-';
                $ap['rt']          = (string) ($location['rt'] ?? '');
                $ap['rw']          = (string) ($location['rw'] ?? '');
                $ap['id_lifemedia'] = $location['id_lifemedia'] ?? '-';

                $ap['connected'] =
                    count($clients['5G'] ?? []) +
                    count($clients['2_4G'] ?? []) +
                    count($clients['unknown'] ?? []);

                // Apply search filter if provided - support multi-term and include location fields
                if ($search) {
                    $s = trim((string) $search);
                    $terms = preg_split('/\s+/', strtolower($s));

                    $hay = strtolower(
                        ($ap['sn'] ?? '') . ' ' .
                        ($ap['model'] ?? '') . ' ' .
                        ($ap['location'] ?? '') . ' ' .
                        ($ap['kemantren'] ?? '') . ' ' .
                        ($ap['kelurahan'] ?? '')
                    );

                    $found = true;
                    foreach ($terms as $t) {
                        if ($t === '')
                            continue;
                        if (strpos($hay, $t) === false) {
                            $found = false;
                            break;
                        }
                    }

                    if (!$found) {
                        continue;
                    }
                }

                // This item matches search (or no search). Count it and add to page buffer if within range.
                if ($collected >= $start && count($pageItems) < $perPage) {
                    $pageItems[] = $ap;
                }

                $collected++;
            }

            $aps = new LengthAwarePaginator(
                $pageItems,
                $collected,
                $perPage,
                $page,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );

            return view('connectedUsers.connectUsers', [
                'aps' => $aps,
                'error' => null,
                'search' => $search ?? null,
            ]);

        } catch (\Throwable $e) {
            \Log::error('ConnectedUsers error: ' . $e->getMessage());
            $perPage = $request->get('perPage', 5);
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

            return view('connectedUsers.connectUsers', [
                'aps' => $emptyPaginator,
                'error' => 'Gagal mengambil data: ' . $e->getMessage(),
            ]);
        }
    }

    public function api(Request $request)
    {
        try {
            $onuService = app(OnuApiService::class);
            $raw = $onuService->getAllOnuWithClients();

            if (!is_array($raw)) {
                return response()->json(['error' => 'Invalid API response'], 500);
            }

            $locationByIp = Cache::remember(
                'ont_location_by_ip',
                86400,
                fn() => $this->readLocationMapByIp()
            );

            $data = [];
            foreach ($raw as $ap) {
                $sn    = $this->normalizeSn($ap['sn'] ?? null) ?? ($ap['sn'] ?? '-');
                $apiIp = trim($ap['ip'] ?? '');
                $info  = $apiIp ? $locationByIp->get($apiIp) : null;

                $data[] = [
                    'Lokasi'    => $info['location']  ?? '-',
                    'SN'        => $sn,
                    'Model'     => $ap['model']        ?? '-',
                    'IP'        => $apiIp               ?: '-',
                    'Kemantren' => $info['kemantren']  ?? '-',
                    'Kelurahan' => $info['kelurahan']  ?? '-',
                    'RT/RW'     => ($info['rt'] ?? '-') . ' / ' . ($info['rw'] ?? '-'),
                    'State'     => ucfirst($ap['state'] ?? 'unknown'),
                ];
            }

            return response()->json($data);

        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /* =========================
     | UTILS
     ========================= */

    private function normalizeSn(?string $sn): ?string
    {
        if (!$sn)
            return null;

        // Trim whitespace dan newlines
        $sn = trim($sn);
        
        // Remove prefix model jika ada (F609-, F612W-, dll)
        if (str_contains($sn, '-')) {
            $sn = trim(substr($sn, strrpos($sn, '-') + 1));
        }

        // Uppercase dan remove semua whitespace tersembunyi
        $sn = strtoupper(preg_replace('/\s+/', '', $sn));

        return $sn;
    }

}

