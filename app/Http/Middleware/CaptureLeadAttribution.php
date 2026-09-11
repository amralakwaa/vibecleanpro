<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * First-touch attribution for the Quote/Contact forms: a visitor often
 * lands on a Service or Article page from a UTM-tagged ad/post, browses,
 * and only submits the lead form several pages later - by then the
 * original query string is long gone. This stores utm_* and the first
 * landing path in the session once, on the first request that carries
 * them, and never overwrites an existing session value (first touch, not
 * last touch).
 */
class CaptureLeadAttribution
{
    private const UTM_KEYS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('lead_attribution.landing_page')) {
            $request->session()->put('lead_attribution.landing_page', $request->path() === '/' ? '/' : '/'.ltrim($request->path(), '/'));
        }

        foreach (self::UTM_KEYS as $key) {
            if ($request->filled($key) && ! $request->session()->has("lead_attribution.{$key}")) {
                $request->session()->put("lead_attribution.{$key}", (string) $request->query($key));
            }
        }

        return $next($request);
    }
}
