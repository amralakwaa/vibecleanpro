<?php

namespace App\Support\Tracking;

use App\Models\Page;
use App\Seo\UrlResolver;

/**
 * The short code appended to a WhatsApp message (e.g. "V-1K3F9Q") so a
 * chat logged as a lead can be traced back to the page it started on.
 * The browser computes it from the page path with the same FNV-1a hash
 * (resources/js/tracking.js) - no lookup, no stored state; the server
 * reverses it by hashing the paths of published pages.
 */
class AttributionCode
{
    public const PREFIX = 'V-';

    public function __construct(private readonly UrlResolver $urls) {}

    public static function forPath(string $path): string
    {
        $hash = 0x811C9DC5;

        foreach (unpack('C*', PageContextResolver::normalizePath($path)) as $byte) {
            $hash ^= $byte;
            $hash = ($hash * 0x01000193) & 0xFFFFFFFF;
        }

        return self::PREFIX.strtoupper(str_pad(base_convert((string) $hash, 10, 36), 7, '0', STR_PAD_LEFT));
    }

    /**
     * The published page whose path produces this code, if any.
     */
    public function resolve(string $code): ?Page
    {
        $code = strtoupper(trim($code));

        if (! str_starts_with($code, self::PREFIX)) {
            $code = self::PREFIX.$code;
        }

        foreach (Page::query()->published()->get(['id', 'type', 'slug']) as $page) {
            if (self::forPath($this->urls->pathForPage($page)) === $code) {
                return $page;
            }
        }

        return null;
    }
}
