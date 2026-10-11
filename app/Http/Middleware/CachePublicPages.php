<?php

namespace App\Http\Middleware;

use App\Support\Cache\PageCache;
use App\Support\Cache\PageCacheSettings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves the public site's finished HTML from cache, so a visitor costs no
 * database connection (see App\Support\Cache\PageCache for why that is the
 * constraint that matters here).
 *
 * This middleware is appended to the "web" group, which puts it INSIDE the
 * session and cookie middleware on purpose. A cached hit therefore still
 * runs CaptureLeadAttribution - ad traffic keeps its attribution - and the
 * response it returns is decorated on the way out with this visitor's own
 * session and XSRF cookies. Only the body and content type are ever
 * stored; headers and cookies are not, because replaying another visitor's
 * Set-Cookie would hand them someone else's session.
 *
 * A page carrying a CSRF token is never stored, which is a property of the
 * body rather than a list of paths to keep in sync: the lead forms, and
 * anything else that grows a form later, drop out automatically instead of
 * being served a token that belongs to the first visitor who arrived.
 */
class CachePublicPages
{
    public function __construct(
        private readonly PageCache $cache,
        private readonly PageCacheSettings $settings,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isCacheable($request)) {
            return $next($request);
        }

        $key = $this->cache->keyFor($request->path(), $request->query());

        if ($hit = $this->cache->get($key)) {
            // Laravel puts "no-cache, private" on a normal response and the
            // hit has to carry it too: this cache is the server's, not the
            // browser's, and a visitor holding their own copy would keep
            // seeing an edit-day-old page long after the panel cleared it.
            return response($hit['content'], 200)
                ->header('Content-Type', $hit['type'])
                ->header('Cache-Control', 'no-cache, private')
                ->header('X-Page-Cache', 'HIT');
        }

        $response = $next($request);

        if ($this->isStorable($response)) {
            $this->cache->put(
                $key,
                (string) $response->getContent(),
                (string) $response->headers->get('Content-Type', 'text/html; charset=UTF-8'),
                $this->settings->ttlSeconds(),
            );
        }

        $response->headers->set('X-Page-Cache', 'MISS');

        return $response;
    }

    /**
     * An editor signed into the panel always sees the live page, so a
     * preview after a save is never the previous version.
     */
    private function isCacheable(Request $request): bool
    {
        return $request->isMethod('GET')
            && ! $request->ajax()
            && Auth::guest()
            && $this->settings->enabled();
    }

    private function isStorable(Response $response): bool
    {
        if ($response->getStatusCode() !== 200) {
            return false;
        }

        if (! str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            return false;
        }

        $content = (string) $response->getContent();

        return $content !== '' && ! str_contains($content, 'name="_token"');
    }
}
