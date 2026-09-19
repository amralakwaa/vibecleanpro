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
            $request->session()->put('lead_attribution.referrer_host', $this->externalReferrerHost($request));
        }

        foreach (self::UTM_KEYS as $key) {
            if ($request->filled($key) && ! $request->session()->has("lead_attribution.{$key}")) {
                $request->session()->put("lead_attribution.{$key}", (string) $request->query($key));
            }
        }

        return $next($request);
    }

    /**
     * Only the host is kept (never the full referring URL), and an internal
     * referrer counts as none - the first request of a session with a
     * same-site referer is a returning tab, not a new traffic source.
     */
    private function externalReferrerHost(Request $request): ?string
    {
        $host = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST);

        return is_string($host) && $host !== $request->getHost() ? mb_substr(mb_strtolower($host), 0, 100) : null;
    }
}
