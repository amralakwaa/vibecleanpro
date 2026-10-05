<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Cache keys and flush helpers for the public pages whose data is identical
 * for every visitor and changes only when an editor saves content.
 *
 * Only the query results are cached, never the rendered HTML - the Blade
 * views still render per request, so the CSRF token and any per-request
 * state stay correct. Freshness is event-driven: AppServiceProvider flushes
 * these keys on save/delete of the models each payload is built from, so an
 * edit in the admin panel is reflected immediately. The long TTL is only a
 * backstop for a missed event (e.g. a pivot-only change).
 */
class PublicPageCache
{
    /** Scalar <head> data for the homepage (HomeController::buildSeoArgs). */
    public const HOME_SEO = 'public:home:seo:v1';

    /** Rendered homepage content HTML (HomeController::buildContentData). */
    public const HOME_CONTENT = 'public:home:content:v1';

    /** Scalar preload attributes for the homepage LCP image (hero photo). */
    public const HOME_LCP = 'public:home:lcp:v1';

    /** Nav + legal + trust links derived from Page lookups in the public layout. */
    public const LAYOUT_CHROME = 'public:layout:chrome:v1';

    /**
     * Failsafe lifetime (seconds) for every key here. Freshness is event-driven
     * (model saves/deletes and pivot $touches flush immediately); this TTL only
     * bounds staleness for a write path that fires no model event at all - a
     * raw query-builder mass update or a direct DB change - to at most one hour.
     */
    public const TTL_SECONDS = 3600;

    public static function flushHome(): void
    {
        Cache::forget(self::HOME_SEO);
        Cache::forget(self::HOME_CONTENT);
        Cache::forget(self::HOME_LCP);
    }

    public static function flushLayoutChrome(): void
    {
        Cache::forget(self::LAYOUT_CHROME);
    }
}
