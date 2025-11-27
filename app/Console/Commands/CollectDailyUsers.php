<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CollectDailyUsers extends Command
{
    protected $signature = 'collect:daily-users';
    protected $description = 'Collect unique wifi users from API and store daily count';

    public function handle()
    {
        try {
            $this->info('Fetching API...');
            $response = Http::timeout(10)->get('http://172.16.100.26:67/api/onu/connect');

            if (! $response->ok()) {
                Log::warning('CollectDailyUsers: API returned non-ok status: ' . $response->status());
                $this->error('API not OK');
                return 1;
            }

            $connections = $response->json();

            if (!is_array($connections)) {
                Log::warning('CollectDailyUsers: unexpected response structure');
                $this->error('Unexpected response');
                return 1;
            }

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
                    // normalisasi mac (opsional): hapus '-' atau '.' dsb
                    $mac = preg_replace('/[^A-F0-9:]/i', '', $mac);
                    if ($mac === '') continue;
                    $macSet[$mac] = true;
                }
            }

            $uniqueCount = count($macSet);
            $date = now()->toDateString();

            // Simpan (updateOrInsert)
            DB::table('daily_user_stats')->updateOrInsert(
                ['date' => $date],
                [
                    'user_count' => $uniqueCount,
                    'meta' => json_encode(['sample_count' => $uniqueCount, 'collected_at' => now()->toDateTimeString()]),
                    'updated_at' => now()
                ]
            );

            $this->info("Saved {$uniqueCount} unique users for {$date}");
            return 0;
        } catch (\Throwable $e) {
            Log::error('CollectDailyUsers error: '.$e->getMessage());
            $this->error('Exception: '.$e->getMessage());
            return 1;
        }
    }
}
