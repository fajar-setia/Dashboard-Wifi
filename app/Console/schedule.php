<?php

// Update hourly stats every minute (real-time data from API)
$schedule->command('update:hourly-stats')
    ->everyMinute()
    ->timezone('Asia/Jakarta')
    ->runInBackground();

// Run the daily user stats aggregation at midnight Asia/Jakarta
$schedule->command('stats:update-daily-users')
    ->dailyAt('00:00')
    ->timezone('Asia/Jakarta')
    ->runInBackground();
