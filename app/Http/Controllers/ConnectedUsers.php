<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Services\OnuApiService;

class ConnectedUsers extends Controller
{
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

            // Try to get ONT -> location map from cache (populated by DashboardController)
            $ontMap = cache()->get('ont_map_paket_all', []);

            $start = max(0, ($page - 1) * $perPage);
            $collected = 0; // total matched items
            $pageItems = [];

            foreach ($raw as $ap) {
                $clients = $ap['wifiClients'] ?? [];

                // Attach mapping data if available
                $rawSn = (string) ($ap['sn'] ?? '');
                $snTrim = trim($rawSn);
                $snKey1 = strtoupper($snTrim);
                $snKey2 = preg_replace('/[^A-Z0-9]/', '', $snKey1); // remove non-alnum for fallback

                $info = null;
                if ($snKey1 && isset($ontMap[$snKey1])) {
                    $info = $ontMap[$snKey1];
                } elseif ($snKey2 && isset($ontMap[$snKey2])) {
                    $info = $ontMap[$snKey2];
                }

                // normalize stored SN to cleaned form if possible
                $ap['sn'] = $snKey1 !== '' ? $snKey1 : ($ap['sn'] ?? '');

                // prefer mapping info, otherwise try API-provided fields; coerce to strings
                $ap['location'] = is_array($info['location'] ?? null) ? implode(', ', $info['location']) : ($info['location'] ?? ($ap['location'] ?? '-'));
                $ap['kemantren'] = is_array($info['kemantren'] ?? null) ? implode(', ', $info['kemantren']) : ($info['kemantren'] ?? ($ap['kemantren'] ?? '-'));
                $ap['kelurahan'] = is_array($info['kelurahan'] ?? null) ? implode(', ', $info['kelurahan']) : ($info['kelurahan'] ?? ($ap['kelurahan'] ?? '-'));
                $ap['rt'] = (string) ($info['rt'] ?? ($ap['rt'] ?? '-'));
                $ap['rw'] = (string) ($info['rw'] ?? ($ap['rw'] ?? '-'));
                $ap['ip'] = (string) ($info['ip'] ?? ($ap['ip'] ?? '-'));
                $ap['pic'] = (string) ($info['pic'] ?? ($ap['pic'] ?? '-'));
                $ap['coordinate'] = (string) ($info['coordinate'] ?? ($ap['coordinate'] ?? '-'));

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

            $ontMap = cache()->get('ont_map_paket_all', []);
            $data = [];

            foreach ($raw as $ap) {
                $rawSn = (string) ($ap['sn'] ?? '');
                $snTrim = trim($rawSn);
                $snKey1 = strtoupper($snTrim);
                $snKey2 = preg_replace('/[^A-Z0-9]/', '', $snKey1);

                $info = null;
                if ($snKey1 && isset($ontMap[$snKey1])) {
                    $info = $ontMap[$snKey1];
                } elseif ($snKey2 && isset($ontMap[$snKey2])) {
                    $info = $ontMap[$snKey2];
                }

                $data[] = [
                    'Lokasi' => $info['location'] ?? ($ap['location'] ?? '-'),
                    'SN' => $snKey1 !== '' ? $snKey1 : ($ap['sn'] ?? '-'),
                    'Model' => $ap['model'] ?? '-',
                    'IP' => $ap['ip'] ?? '-',
                    'Kemantren' => $info['kemantren'] ?? ($ap['kemantren'] ?? '-'),
                    'Kelurahan' => $info['kelurahan'] ?? ($ap['kelurahan'] ?? '-'),
                    'RT/RW' => ($info['rt'] ?? '-') . ' / ' . ($info['rw'] ?? '-'),
                    'State' => ucfirst($ap['state'] ?? 'unknown'),
                ];
            }

            return response()->json($data);

        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
