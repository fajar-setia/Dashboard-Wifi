<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';

$db = $app->make('db');
$results = $db->table('daily_user_stats_hourly')
    ->where('date', '2026-01-29')
    ->orderBy('hour')
    ->get(['hour', 'user_count'])
    ->toArray();

echo "Hourly stats for 2026-01-29:\n";
foreach ($results as $row) {
    echo "Hour " . str_pad($row->hour, 2, '0', STR_PAD_LEFT) . ": " . $row->user_count . " users\n";
}
