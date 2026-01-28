<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GenerateHourlyStats extends Command
{
    protected $signature = 'generate:hourly-stats {--date=}';
    protected $description = 'Generate hourly user stats for a given date';

    public function handle()
    {
        $date = $this->option('date') ?? now()->toDateString();
        $date = Carbon::parse($date)->toDateString();

        $this->info("Generating hourly stats for: {$date}");

        // Delete existing data for this date
        DB::table('daily_user_stats_hourly')->where('date', $date)->delete();

        // Generate sample hourly data (you can replace with actual data logic)
        for ($hour = 0; $hour < 24; $hour++) {
            // Create a wave pattern: low at night, peak in afternoon
            $baseValue = 20;
            $wave = sin(($hour - 6) * M_PI / 12) * 40 + 40;
            $userCount = max(0, (int) $wave + rand(-5, 5));

            DB::table('daily_user_stats_hourly')->insert([
                'date' => $date,
                'hour' => $hour,
                'user_count' => $userCount,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->line("Hour {$hour}: {$userCount} users");
        }

        $this->info('Hourly stats generated successfully!');
    }
}
