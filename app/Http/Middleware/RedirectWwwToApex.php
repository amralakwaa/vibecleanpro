<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * www.vibecleanpro.com and vibecleanpro.com both resolve to the same
 * server, so without this the whole site answers 200 on two hostnames.
 * The canonical tag already points search engines at the apex, but a
 * duplicate that merely declares its canonical still splits links and
 * still lets a visitor sit on the wrong hostname; a 301 settles it for
 * crawlers and browsers alike, and is where a www redirect belongs -
 * in the application, not in a CDN rule that disappears the moment the
 * CDN is reconfigured (which is exactly how this one went missing).
 *
 * The apex comes from APP_URL, so no hostname is hard-coded and a
 * deployment whose APP_URL is itself a www host redirects nothing.
 */
class RedirectWwwToApex
{
    public function handle(Request $request, Closure $next): Response
    {
        $apex = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (! is_string($apex) || str_starts_with($apex, 'www.')) {
            return $next($request);
        }

        if (strcasecmp($request->getHost(), 'www.'.$apex) !== 0) {
            return $next($request);
        }

        $target = $request->getScheme().'://'.$apex.$request->getRequestUri();

        return redirect()->away($target, 301);
    }
}
