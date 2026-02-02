<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        // Update hourly stats every minute (real-time data from API)
        $schedule->command('update:hourly-stats')
            ->everyMinute()
            ->timezone('Asia/Jakarta')
            ->withoutOverlapping(5) // Prevent overlap with 5 min timeout
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/cron-hourly-stats.log'));

        // Collect realtime location stats every 5 minutes
        $schedule->command('stats:collect-realtime')
            ->everyFiveMinutes()
            ->timezone('Asia/Jakarta')
            ->withoutOverlapping(10) // Prevent overlap with 10 min timeout
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/cron-realtime-stats.log'));

        // Run the daily user stats aggregation at midnight Asia/Jakarta
        $schedule->command('stats:update-daily-users')
            ->dailyAt('00:00')
            ->timezone('Asia/Jakarta')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/cron-daily-stats.log'));

        // Cleanup old stats (optional - keep last 90 days)
        $schedule->command('stats:cleanup-old')
            ->daily()
            ->at('01:00')
            ->timezone('Asia/Jakarta')
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}