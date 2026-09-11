<?php

use App\Http\Middleware\CaptureLeadAttribution;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Only routes/web.php (the public site) resolves through the
        // named "web" group - Filament's admin panel registers its own
        // explicit middleware stack (see AdminPanelProvider), so this
        // never touches /admin.
        $middleware->appendToGroup('web', CaptureLeadAttribution::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
