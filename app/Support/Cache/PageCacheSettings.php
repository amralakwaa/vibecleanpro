<?php

namespace App\Support\Cache;

use App\Support\Settings\IntegrationSettings;

/**
 * Panel-managed configuration for the full-page cache, read on every
 * public request - so it stays deliberately cheap and never throws: a
 * fresh install without the settings table must still serve pages.
 */
class PageCacheSettings
{
    public const DEFAULT_TTL_MINUTES = 10;

    public const MIN_TTL_MINUTES = 1;

    public const MAX_TTL_MINUTES = 1440;

    public function __construct(private readonly IntegrationSettings $settings) {}

    /**
     * Off until an editor turns it on. A cache that appears by surprise is
     * a cache nobody remembers to clear when a page looks stale.
     */
    public function enabled(): bool
    {
        return rescue(fn (): bool => $this->settings->bool(IntegrationSettings::PAGE_CACHE_ENABLED), false, false);
    }

    public function ttlMinutes(): int
    {
        $minutes = rescue(
            fn (): int => $this->settings->int(IntegrationSettings::PAGE_CACHE_TTL_MINUTES, self::DEFAULT_TTL_MINUTES),
            self::DEFAULT_TTL_MINUTES,
            false,
        );

        return max(self::MIN_TTL_MINUTES, min(self::MAX_TTL_MINUTES, $minutes));
    }

    public function ttlSeconds(): int
    {
        return $this->ttlMinutes() * 60;
    }
}
