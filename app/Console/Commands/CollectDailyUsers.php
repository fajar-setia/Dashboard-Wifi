<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\OnuApiService;

class CollectDailyUsers extends Command
{
    protected $signature = 'collect:daily-users';
    protected $description = 'Collect peak daily unique wifi users';

    public function handle()
    {
        try {
            $this->info('Fetching data from OnuApiService...');
            $service = new OnuApiService();
            $connections = $service->getAllOnuWithClients();

            if (!is_array($connections)) {
                Log::warning('Invalid connections data from service');
                $this->error('Invalid connections data');
                return 1;
            }

            // Hitung unique MAC
            $macSet = [];
            foreach ($connections as $c) {
                if (!isset($c['wifiClients']) || !is_array($c['wifiClients'])) {
                    continue;
                }

                $wifi = $c['wifiClients'];
                $all = array_merge(
                    $wifi['5G'] ?? [],
                    $wifi['2_4G'] ?? [],
                    $wifi['unknown'] ?? []
                );

                foreach ($all as $client) {
                    if (!isset($client['wifi_terminal_mac'])) continue;
                    $mac = strtoupper(trim($client['wifi_terminal_mac']));
                    $mac = preg_replace('/[^A-F0-9:]/i', '', $mac);
                    if ($mac === '') continue;
                    $macSet[$mac] = true;
                }
            }

            $uniqueCount = count($macSet);
            $this->info("Found {$uniqueCount} unique users");

            // Simpan ke database sebagai peak harian
            DB::statement("
                INSERT INTO daily_user_stats (date, user_count, meta, updated_at)
                VALUES (
                    CURRENT_DATE,
                    ?,
                    JSON_OBJECT(
                        'sample_count', ?,
                        'collected_at', NOW()
                    ),
                    NOW()
                )
                ON DUPLICATE KEY UPDATE
                    user_count = GREATEST(user_count, VALUES(user_count)),
                    updated_at = NOW()
            ", [$uniqueCount, $uniqueCount]);

            // SIMPAN PER LOKASI
            $this->savePerLocation($connections);

            Log::info('CollectDailyUsers: collected', ['unique_users' => $uniqueCount]);
            $this->info('Daily users collection completed');
            return 0;

        } catch (\Throwable $e) {
            Log::error('CollectDailyUsers error: ' . $e->getMessage());
            $this->error('Failed to collect daily users: ' . $e->getMessage());
            return 1;
        }
    }
    }

    private function savePerLocation($connections)
    {
        $ontMap = cache()->remember('ont_map_paket_all', 600, fn() => $this->readOntMap());

        $globalMacSet = [];  // Track unique MAC secara global
        $locationData = [];

        foreach ($connections as $c) {
            $sn = strtoupper(trim($c['sn'] ?? ''));
            $info = $ontMap[$sn] ?? null;
            $location = $info['location'] ?? '-';
            $kemantren = $info['kemantren'] ?? '-';

            if (!isset($locationData[$location])) {
                $locationData[$location] = [
                    'kemantren' => $kemantren,
                    'sn' => $sn,
                    'users' => []
                ];
            }

            if (!isset($c['wifiClients']) || !is_array($c['wifiClients'])) {
                continue;
            }

            $wifi = $c['wifiClients'];
            $all = array_merge(
                $wifi['5G'] ?? [],
                $wifi['2_4G'] ?? [],
                $wifi['unknown'] ?? []
            );

            foreach ($all as $client) {
                if (!isset($client['wifi_terminal_mac'])) continue;
                $mac = strtoupper(trim($client['wifi_terminal_mac']));
                $mac = preg_replace('/[^A-F0-9:]/i', '', $mac);
                if ($mac === '') continue;

                // Hanya catat jika MAC belum pernah tercatat di lokasi manapun
                if (!isset($globalMacSet[$mac])) {
                    $locationData[$location]['users'][$mac] = true;
                    $globalMacSet[$mac] = $location;  // Tandai MAC sudah tercatat
                }
            }
        }

        foreach ($locationData as $location => $data) {
            $count = count($data['users']);

            if ($count === 0) {
                continue;
            }

            DB::statement("
                INSERT INTO daily_location_stats
                    (date, location, kemantren, sn, user_count, created_at, updated_at)
                VALUES (
                    CURRENT_DATE,
                    ?,
                    ?,
                    ?,
                    ?,
                    NOW(),
                    NOW()
                )
                ON DUPLICATE KEY UPDATE
                    user_count = GREATEST(user_count, VALUES(user_count)),
                    updated_at = NOW()
            ", [
                $location,
                $data['kemantren'],
                $data['sn'],
                $count
            ]);
        }
    }

    private function readOntMap(): array
    {
        $map = [];
        try {
            $paket110_id = '1Wtkfylu-BbdIzvV7ZT_M7rEOg2ANBh5ylvea1sp37m8';
            $client = new \Google\Client;
            $client->setApplicationName('Laravel Dashboard');
            $client->setScopes([\Google\Service\Sheets::SPREADSHEETS_READONLY]);
            $client->setAuthConfig(config_path('google/service-accounts.json'));
            $service = new \Google\Service\Sheets($client);

            foreach (['paket 110' => $paket110_id, 'paket 200' => config('services.google.sheet_id')] as $sheet => $id) {
                $range = "'{$sheet}'!B2:I201";
                $rows = $service->spreadsheets_values->get($id, $range)->getValues() ?? [];

                foreach ($rows as $row) {
                    $sn = trim($row[7] ?? '');
                    if ($sn === '') continue;
                    $map[strtoupper($sn)] = [
                        'location' => trim($row[0] ?? ''),
                        'kemantren' => trim($row[1] ?? ''),
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::error('readOntMap: ' . $e->getMessage());
        }
        return $map;
    }
}
