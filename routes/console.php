<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Console\Commands\CollectDailyUsers;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule::command('collect:daily-users')
//         ->everySixHours()
//         ->appendOutputTo(storage_path('logs/scheduler.log'))
//         ->withoutOverlapping();

// Disabled - using real-time updates instead
