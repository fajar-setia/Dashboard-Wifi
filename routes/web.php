<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
<<<<<<< Updated upstream
use App\Http\Controllers\AccessPointController;
=======
use App\Http\Controllers\ConnectedUsers;
>>>>>>> Stashed changes

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

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

require __DIR__.'/auth.php';
