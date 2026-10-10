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

        // These ranges are currently INERT, and nothing about visitor IPs
        // depends on them - do not assume otherwise when changing this.
        //
        // The origin has always sat behind Hostinger's own edge, so the
        // immediate peer is a Hostinger address and never a Cloudflare one;
        // these ranges have therefore never matched. The real client IP
        // reaches PHP regardless, because that edge rewrites REMOTE_ADDR
        // before the application sees it. Measured, not assumed: two sources
        // hitting the throttled /e endpoint at the same moment get separate
        // rate-limit buckets, which only happens if $request->ip() is the
        // true client address.
        //
        // Cloudflare was proxying in front of that edge until Oct 2026, when
        // the stacked pair made the edge treat the whole audience as one
        // visitor and answer 429 site-wide; Cloudflare is now DNS-only. The
        // list is kept so that re-enabling the orange cloud does not silently
        // change IP resolution - but note that while Cloudflare is NOT in
        // front, trusting its ranges is a (small) spoofing surface, because
        // the origin is directly reachable. Drop the list if the proxy is
        // staying off for good. Source: https://www.cloudflare.com/ips/
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
