<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\AlertNotifController;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    public function boot()
    {
        View::composer('layouts.navigation', function ($view) {
            $alertCount = AlertNotifController::getAlertCount();
            $view->with('alertCount', $alertCount);
        });

    }
}
