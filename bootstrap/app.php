<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Client\ConnectionException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withSchedule(function ($schedule) {
        require __DIR__.'/../app/Console/schedule.php';
    })
    ->withExceptions(function (Exceptions $exceptions) {

        $exceptions->render(function (
            ConnectionException $e,
            $request
        ) {
            return response()->json([
                'message' => 'timeout',
            ], 408);
        });

    })
    ->create();
