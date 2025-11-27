<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Console\Commands\CollectDailyUsers;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(new CollectDailyUsers)
        ->dailyAt('23:55')        // atau ->everyTenMinutes() untuk test
        ->withoutOverlapping()     // hindari bentrok kalau cron lama blm selesai
        ->onOneServer();  