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
            $response = Http::timeout(10)->get('http://172.16.100.26:67/api/onu/connect');

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

            $this->info('Daily peak saved successfully');
            return 0;

        } catch (\Throwable $e) {
            Log::error('CollectDailyUsers error: '.$e->getMessage());
            $this->error('Exception: '.$e->getMessage());
            return 1;
        }
    }
}
