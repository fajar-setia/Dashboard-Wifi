<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AccessPointController;
use App\Http\Controllers\ConnectedUsers;
use App\Http\Controllers\AlertController;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/', function() {
    return redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/dashboard/user-online', [DashboardController::class, 'getUserOnline'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.user-online');

Route::get('/dashboard/weekly-user-data', [DashboardController::class, 'getWeeklyUserData'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.weekly-user-data');

Route::get('/dashboard/daily-user-data-by-hour', [DashboardController::class, 'getDailyUserDataByHour'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.daily-user-data-by-hour');

Route::get('/dashboard/monthly-location-data', [DashboardController::class, 'monthlyLocationData'])
    ->middleware(['auth','verified']);

Route::get('/dashboard/monthly-user-data', [DashboardController::class, 'monthlyUserData'])
    ->middleware(['auth','verified']);

Route::get('/dashboard/location-clients', [DashboardController::class, 'locationClients'])
    ->middleware(['auth','verified']);

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/access-point', [AccessPointController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('access-point');

Route::get('/connectUser', [ConnectedUsers::class, 'index'])
    ->name('connectUser');

Route::get('/alert', [AlertController::class, 'index'])->name('alert');

require __DIR__.'/auth.php';
