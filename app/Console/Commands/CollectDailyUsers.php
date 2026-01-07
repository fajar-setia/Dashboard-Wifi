<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CollectDailyUsers extends Command
{
    protected $signature = 'collect:daily-users';
    protected $description = 'Collect peak daily unique wifi users';

    public function handle()
    {
        try {
            $this->info('Fetching API...');
            $response = Http::timeout(10)->get('http://172.16.105.26:6767/api/onu/connect');

            if (! $response->ok()) {
                Log::warning('API not OK: '.$response->status());
                $this->error('API not OK');
                return 1;
            }

            $connections = $response->json();
            if (!is_array($connections)) {
                Log::warning('Invalid API response');
                $this->error('Invalid API response');
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

            $currentCount = count($macSet);
            $this->info("Current unique users: {$currentCount}");

            // SIMPAN PEAK HARIAN (UPSERT + GREATEST)
            DB::statement("
                INSERT INTO daily_user_stats (date, user_count, meta, updated_at)
                VALUES (
                    CURRENT_DATE,
                    {$currentCount},
                    json_build_object(
                        'sample_count', {$currentCount},
                        'collected_at', now()
                    ),
                    now()
                )
                ON CONFLICT (date)
                DO UPDATE SET
                    user_count = GREATEST(daily_user_stats.user_count, EXCLUDED.user_count),
                    updated_at = now()
            ");

            // SIMPAN PER LOKASI
            $this->savePerLocation($connections);

            $this->info('Daily peak saved successfully');
            return 0;

        } catch (\Throwable $e) {
            Log::error('CollectDailyUsers error: '.$e->getMessage());
            $this->error('Exception: '.$e->getMessage());
            return 1;
        }
    }

    private function savePerLocation($connections)
    {
        $ontMap = cache()->remember('ont_map_paket_all', 600, fn() => $this->readOntMap());

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
                $locationData[$location]['users'][$mac] = true;
            }
        }

        foreach ($locationData as $location => $data) {
            $count = count($data['users']);
            DB::statement("
                INSERT INTO daily_location_stats (date, location, kemantren, sn, user_count, created_at, updated_at)
                VALUES (
                    CURRENT_DATE,
                    ?,
                    ?,
                    ?,
                    {$count},
                    now(),
                    now()
                )
                ON CONFLICT (date, location, sn)
                DO UPDATE SET
                    user_count = GREATEST(daily_location_stats.user_count, EXCLUDED.user_count),
                    updated_at = now()
            ", [$location, $data['kemantren'], $data['sn']]);
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
