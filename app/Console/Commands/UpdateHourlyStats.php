<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\OnuApiService;

class UpdateHourlyStats extends Command
{
    protected $signature = 'update:hourly-stats';
    protected $description = 'Update hourly user stats from API every hour';

    public function handle()
    {
        $now = Carbon::now('Asia/Jakarta');
        $today = $now->toDateString();
        $currentHour = (int) $now->format('H');

        $this->info("Updating hourly stats for {$today} hour {$currentHour} (Asia/Jakarta)");

        try {
            $onuService = app(OnuApiService::class);
            $connections = $onuService->getAllOnuWithClients();

            // Count unique users
            $uniqueMacSet = [];
            if (is_array($connections)) {
                foreach ($connections as $c) {
                    if (!is_array($c) || !isset($c['wifiClients'])) {
                        continue;
                    }
                    $wifi = $c['wifiClients'];
                    $allClients = array_merge(
                        $wifi['5G'] ?? [],
                        $wifi['2_4G'] ?? [],
                        $wifi['unknown'] ?? []
                    );

                    foreach ($allClients as $client) {
                        if (!isset($client['wifi_terminal_mac'])) {
                            continue;
                        }
                        $mac = strtoupper(trim($client['wifi_terminal_mac']));
                        $mac = preg_replace('/[^A-F0-9:]/i', '', $mac);
                        if ($mac === '') {
                            continue;
                        }
                        $uniqueMacSet[$mac] = true;
                    }
                }
            }

            $userCount = count($uniqueMacSet);

            // Update or insert hourly stat for current hour
            DB::table('daily_user_stats_hourly')->updateOrInsert(
                ['date' => $today, 'hour' => $currentHour],
                [
                    'user_count' => $userCount,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $this->info("Hour {$currentHour}: {$userCount} users recorded");

            // Zero out all future hours (hours that haven't occurred yet)
            // Contoh: jika sekarang jam 5 pagi, zero out jam 6-23
            for ($futureHour = $currentHour + 1; $futureHour < 24; $futureHour++) {
                DB::table('daily_user_stats_hourly')->updateOrInsert(
                    ['date' => $today, 'hour' => $futureHour],
                    [
                        'user_count' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
                $this->line("Hour {$futureHour}: zeroed out (future hour)");
            }

        } catch (\Throwable $e) {
            $this->error('Error updating hourly stats: ' . $e->getMessage());
        }
    }
}
