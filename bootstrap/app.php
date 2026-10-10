<?php

use App\Http\Middleware\CaptureLeadAttribution;
use App\Http\Middleware\RedirectWwwToApex;
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
        // Global, not web-group: the apex redirect is about the hostname,
        // so it has to apply to every entry point the server answers on -
        // the public site, /admin, sitemap.xml and robots.txt alike.
        $middleware->prepend(RedirectWwwToApex::class);

        // Only routes/web.php (the public site) resolves through the
        // named "web" group - Filament's admin panel registers its own
        // explicit middleware stack (see AdminPanelProvider), so this
        // never touches /admin.
        $middleware->appendToGroup('web', CaptureLeadAttribution::class);

        // When the site is served through Cloudflare, the origin only ever
        // sees Cloudflare's IPs; the real visitor IP and the original scheme
        // arrive in X-Forwarded-*. Trust exactly Cloudflare's published ranges
        // (never "*", because the Hostinger origin is also reachable directly)
        // so https:// URL generation, the canonical tag and lead-attribution
        // tracking all use the visitor's real connection, not Cloudflare's.
        // Before Cloudflare is live these ranges simply never match, so this is
        // a no-op until cutover. Source: https://www.cloudflare.com/ips/
        $middleware->trustProxies(at: [
            '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22',
            '103.31.4.0/22', '141.101.64.0/18', '108.162.192.0/18',
            '190.93.240.0/20', '188.114.96.0/20', '197.234.240.0/22',
            '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
            '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
            '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32',
            '2405:b500::/32', '2405:8100::/32', '2a06:98c0::/29',
            '2c0f:f248::/32',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
