<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class UpdateDailyUserStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stats:update-daily-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggregate current connected users and persist into daily_user_stats';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting daily user stats aggregation');

        try {
            $response = Http::timeout(10)->get('http://172.16.105.26:6767/api/onu/connect');

            $count = 0;

            if ($response->ok()) {
                $connections = $response->json();
                if (is_array($connections)) {
                    foreach ($connections as $c) {
                        if (!is_array($c) || !isset($c['wifiClients'])) continue;
                        $wifi = $c['wifiClients'];
                        $count += count($wifi['5G'] ?? []);
                        $count += count($wifi['2_4G'] ?? []);
                        $count += count($wifi['unknown'] ?? []);
                    }
                }
            } else {
                Log::warning('UpdateDailyUserStats: API response not OK');
            }

            $date = now()->toDateString();

            DB::table('daily_user_stats')->updateOrInsert(
                ['date' => $date],
                ['user_count' => $count, 'updated_at' => now(), 'created_at' => now()]
            );

            $this->info("Daily user stats updated: {$count} users on {$date}");
            Log::info('UpdateDailyUserStats: updated', ['date' => $date, 'count' => $count]);

            return 0;
        } catch (\Throwable $e) {
            Log::error('UpdateDailyUserStats error: ' . $e->getMessage());
            $this->error('Failed to update daily user stats: ' . $e->getMessage());
            return 1;
        }
    }
}
