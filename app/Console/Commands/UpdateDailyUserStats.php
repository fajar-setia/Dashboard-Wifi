<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\OnuApiService;

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
            $service = new OnuApiService();
            $connections = $service->getAllOnuWithClients();

            $count = 0;

            if (is_array($connections)) {
                // Hitung unique MAC seperti CollectDailyUsers
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

                $count = count($macSet);
            } else {
                Log::warning('UpdateDailyUserStats: No connections data from service');
                $this->error('No connections data from API');
                return 1;
            }

            // Simpan sebagai peak harian seperti CollectDailyUsers
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
            ", [$count, $count]);

            $this->info("Daily user stats updated: {$count} unique users on " . now()->toDateString());
            Log::info('UpdateDailyUserStats: updated', ['date' => now()->toDateString(), 'unique_users' => $count]);

            return 0;
        } catch (\Throwable $e) {
            Log::error('UpdateDailyUserStats error: ' . $e->getMessage());
            $this->error('Failed to update daily user stats: ' . $e->getMessage());
            return 1;
        }
    }
}
