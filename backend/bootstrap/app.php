<?php

use App\Http\Middleware\ApiKeyAuth;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'api.key' => ApiKeyAuth::class,
            'customer.auth' => \App\Http\Middleware\CustomerAuth::class,
            'rate.limit' => \App\Http\Middleware\RateLimitMiddleware::class,
            'sanitize.input' => \App\Http\Middleware\SanitizeInput::class,
            'secure.upload' => \App\Http\Middleware\SecureFileUpload::class,
        ]);
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule): void {
        // Check for expiring licenses daily at 9 AM
        $schedule->command('licenses:check-expiring --days=30')->dailyAt('09:00');
        $schedule->command('licenses:check-expiring --days=7')->dailyAt('09:00');
        $schedule->command('licenses:check-expiring --days=1')->dailyAt('09:00');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
