<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Two spellings of a public URL used to answer 200 next to the real one:
 * "/index.php" (the front controller named in the address) and a slug
 * typed with capitals ("/services/Home-Cleaning"). The canonical tag
 * already pointed at the real URL, but a 301 is what consolidates links
 * and keeps visitors on one address.
 *
 * Only public content paths of one or two segments are lowercased; deeper
 * paths keep their case on purpose (trust verification codes such as
 * /trust/verify/VCP-CCR-001 are case-significant), and the admin panel,
 * Livewire and tracking endpoints are never touched.
 */
class RedirectToCanonicalPath
{
    private const UNTOUCHED_PREFIXES = ['admin', 'livewire', 'filament', 'e', 'storage', 'build', 'media', 'up'];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        $query = $request->getQueryString();
        $suffix = $query !== null ? '?'.$query : '';
        $uri = $request->getRequestUri();

        if (str_starts_with($uri, '/index.php')) {
            $rest = substr((string) parse_url($uri, PHP_URL_PATH), strlen('/index.php'));

            // Absolute from APP_URL: on a request for /index.php itself the
            // URL generator's base path *is* /index.php, so a relative
            // redirect would point straight back at it.
            return redirect()->away($this->root().'/'.ltrim($rest, '/').$suffix, 301);
        }

        $path = $request->path();

        if (! preg_match('#^[A-Za-z0-9-]+(/[A-Za-z0-9-]+)?$#', $path) || strtolower($path) === $path) {
            return $next($request);
        }

        if (in_array(strtolower(explode('/', $path)[0]), self::UNTOUCHED_PREFIXES, true)) {
            return $next($request);
        }

        return redirect()->away($this->root().'/'.strtolower($path).$suffix, 301);
    }

    private function root(): string
    {
        return rtrim((string) config('app.url'), '/');
    }
}
