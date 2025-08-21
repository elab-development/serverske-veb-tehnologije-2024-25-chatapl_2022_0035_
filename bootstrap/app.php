<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register custom security middlewares
        $middleware->alias([
            'rate.limit' => \App\Http\Middleware\RateLimitMiddleware::class,
            'xss.protection' => \App\Http\Middleware\XssProtectionMiddleware::class,
            'csrf.protection' => \App\Http\Middleware\CsrfProtectionMiddleware::class,
            'input.validation' => \App\Http\Middleware\InputValidationMiddleware::class,
        ]);
        
        // Apply security middlewares globally
        $middleware->append(\App\Http\Middleware\XssProtectionMiddleware::class);
        $middleware->append(\App\Http\Middleware\InputValidationMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
