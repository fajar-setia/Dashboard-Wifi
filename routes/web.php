<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AccessPointController;
use App\Http\Controllers\ConnectedUsers;
use App\Http\Controllers\AlertController;

/*
|--------------------------------------------------------------------------
| Root
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Dashboard (PROTECTED BY AUTH)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('dashboard')->group(function () {

    // Main dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    //Export dashboard data
    Route::get('/dashboard/export', [DashboardController::class, 'exportExcel'])->name('dashboard.export');

    // Location Stats API
    Route::get('/weekly-location-data', [DashboardController::class, 'getWeeklyLocationData'])
        ->name('dashboard.weekly-location-data');

    Route::get('/monthly-location-data', [DashboardController::class, 'monthlyLocationData'])
        ->name('dashboard.monthly-location-data');

    // User Stats API
    Route::get('/user-online', [DashboardController::class, 'getUserOnline'])
        ->name('dashboard.user-online');

    Route::get('/weekly-user-data', [DashboardController::class, 'getWeeklyUserData'])
        ->name('dashboard.weekly-user-data');

    Route::get('/monthly-user-data', [DashboardController::class, 'monthlyUserData'])
        ->name('dashboard.monthly-user-data');

    Route::get('/daily-user-data-by-hour', [DashboardController::class, 'getDailyUserDataByHour'])
        ->name('dashboard.daily-user-data-by-hour');

    // Location Clients
    Route::get('/location-clients', [DashboardController::class, 'locationClients'])
        ->name('dashboard.location-clients');

    // Manual trigger realtime stats collection
    Route::post('/trigger-realtime-stats', function () {
        try {

            Artisan::call('stats:collect-realtime');
            $output = Artisan::output();

            $lines = explode("\n", trim($output));

            return response()->json([
                'success' => true,
                'message' => 'Realtime stats collection triggered successfully',
                'output' => $lines,
                'timestamp' => now()->toIso8601String()
            ]);

        } catch (\Throwable $e) {

            Log::error('Manual trigger realtime stats failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    })->name('dashboard.trigger-realtime-stats');

    // Stats monitoring endpoint
    Route::get('/stats-monitor', function () {
        try {

            $today = now('Asia/Jakarta')->toDateString();

            $locationStats = DB::table('daily_location_stats')
                ->where('date', $today)
                ->orderBy('user_count', 'desc')
                ->get();

            $userStats = DB::table('daily_user_stats')
                ->where('date', $today)
                ->first();

            return response()->json([
                'date' => $today,
                'location_stats' => [
                    'total_users' => $locationStats->sum('user_count'),
                    'total_locations' => $locationStats->count(),
                    'last_update' => $locationStats->max('updated_at'),
                    'top_5' => $locationStats->take(5)->map(fn($s) => [
                        'location' => $s->location,
                        'kemantren' => $s->kemantren,
                        'users' => $s->user_count,
                    ]),
                ],
                'user_stats' => [
                    'total_users' => $userStats->user_count ?? 0,
                    'last_update' => $userStats->updated_at ?? null,
                ],
                'timestamp' => now()->toIso8601String(),
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    })->name('dashboard.stats-monitor');

});

/*
|--------------------------------------------------------------------------
| Profile
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

});

/*
|--------------------------------------------------------------------------
| Other Protected Pages
|--------------------------------------------------------------------------
*/
Route::get('/access-point', [AccessPointController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('access-point');

Route::get('/connectUser', [ConnectedUsers::class, 'index'])
    ->middleware('auth')
    ->name('connectUser');


Route::get('/api/ont', [ConnectedUsers::class, 'api'])
    ->name('api.ont');

Route::get('/alert', [AlertController::class, 'index'])
    ->middleware('auth')
    ->name('alert');

/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/
Route::post('/logout', function () {
    Auth::logout();
    return redirect()->route('login');
})->name('logout');

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
*/
require __DIR__ . '/auth.php';

