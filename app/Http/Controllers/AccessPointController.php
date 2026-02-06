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

    private function loadOntLocations(): Collection
    {
        // Cek multiple paths
        $paths = [
            storage_path('app/public/ACSfiks.csv'),
            public_path('storage/ACSfiks.csv'),  // ← PATH INI
            storage_path('app/ACSfiks.csv'),
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
                        'nama_lokasi' => $r['nama_lokasi'] ?? null,
                        'kelurahan' => $r['kelurahan'] ?? null,
                        'kemantren' => $r['kemantren'] ?? null,
                        'ip' => $r['ip'] ?? null,
                    ]
                ];
            });
    }

    /**
     * SOLUSI ALTERNATIF: Mapping berdasarkan IP atau pola lain
     * Jika SN tidak cocok, coba match by IP atau data lain
     */
    private function findLocationByAlternative($onu, Collection $locationData): ?array
    {
        // Jika ada data IP di ONU, coba match by IP
        if (!empty($onu['ip'])) {
            $found = $locationData->first(function ($loc) use ($onu) {
                return isset($loc['ip']) && $loc['ip'] === $onu['ip'];
            });
            
            if ($found) {
                return $found;
            }
        }

        // Tambahkan logika matching lain di sini jika diperlukan
        // Misalnya by MAC address, by hostname, dll
        
        return null;
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
        $locationData = Cache::remember(
            'ont_locations_v3',
            now()->addDay(),
            fn() => $this->loadOntLocations()
        );

        /* ========= LOG STATISTIK ========= */
        \Log::info('Location Data Stats', [
            'total_locations' => $locationData->count(),
            'total_devices' => count($onuData),
            'sample_csv_sn' => $locationData->keys()->take(5)->toArray(),
            'sample_api_sn' => collect($onuData)->pluck('sn')->take(5)->toArray(),
        ]);

        /* ========= MERGE DATA ========= */
        $devices = collect($onuData)->map(function ($onu) use ($connectData, $locationData) {
            $sn = $this->normalizeSn($onu['sn'] ?? null);
            $connect = $connectData->get($sn, []);
            
            // Primary: Match by SN
            $location = $locationData->get($sn);
            
            // Fallback: Match by alternative method (IP, etc)
            if (!$location) {
                $location = $this->findLocationByAlternative($onu, $locationData);
            }

            $userCount =
                count($connect['wifiClients']['5G'] ?? []) +
                count($connect['wifiClients']['2_4G'] ?? []) +
                count($connect['wifiClients']['unknown'] ?? []);

            // Format lokasi
            $lokasiParts = collect([
                $location['nama_lokasi'] ?? null,
                $location['kelurahan'] ?? null,
                $location['kemantren'] ?? null,
            ])->filter();

            $formattedLokasi = 'Lokasi Tidak Diketahui';
            $isNewDevice = false;
            
            if ($lokasiParts->count() > 0) {
                $formattedLokasi = $lokasiParts->implode(' - ');
            } else {
                // Tandai sebagai device baru yang perlu di-update
                $isNewDevice = true;
                
                // Bisa ditambahkan info IP jika ada
                if (!empty($onu['ip'])) {
                    $formattedLokasi = "Lokasi Tidak Diketahui (IP: {$onu['ip']})";
                }
            }

            return [
                'sn' => $sn,
                'original_sn' => $onu['sn'] ?? null,
                'model' => $onu['model'] ?? '-',
                'state' => strtolower($connect['state'] ?? $onu['state'] ?? 'offline'),
                'lokasi' => $formattedLokasi,
                'lokasi_found' => $lokasiParts->count() > 0,
                'is_new_device' => $isNewDevice,
                'wifi_user_count' => $userCount,
                'wifiClients' => $connect['wifiClients'] ?? [],
            ];
        });

        /* ========= SEARCH ========= */
        if ($search = $request->get('search')) {
            $devices = $devices->filter(
                fn($d) =>
                str_contains(strtolower($d['sn']), strtolower($search)) ||
                str_contains(strtolower($d['model']), strtolower($search)) ||
                str_contains(strtolower($d['lokasi']), strtolower($search))
            );
        }

        /* ========= SUMMARY ========= */
        $summary = [
            'total' => $devices->count(),
            'online' => $devices->where('state', 'online')->count(),
            'offline' => $devices->where('state', '!=', 'online')->count(),
            'users' => $devices->sum('wifi_user_count'),
            'lokasi_ditemukan' => $devices->where('lokasi_found', true)->count(),
            'device_baru' => $devices->where('is_new_device', true)->count(),
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
                'device_baru' => 0,
            ],
            'error' => $error,
            'search' => $request->get('search'),
        ]);
    }
}