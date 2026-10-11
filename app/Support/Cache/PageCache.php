<?php

namespace App\Support\Cache;

use Illuminate\Support\Facades\Cache;

/**
 * Full-page cache for the public site: the finished HTML, not just the
 * query results PublicPageCache already holds.
 *
 * Why it exists: every uncached page render holds one MySQL connection for
 * its whole lifetime, and the hosting account caps concurrent connections.
 * Measured on production, 20 simultaneous requests were served cleanly and
 * 30 returned 17% HTTP 500 with "SQLSTATE[HY000] [2002] Operation not
 * permitted". A served-from-cache request touches no database at all, so
 * the same ceiling carries far more visitors - which is the difference
 * between surviving an ad campaign and paying for error pages.
 *
 * Invalidation is a VERSION COUNTER, not a key sweep: the file cache driver
 * has no tags, and deleting thousands of entries one by one on every
 * editor save would be slower than the thing it protects. Every key is
 * prefixed with the current version, so bumping it retires the whole set
 * at once and the old entries expire on their own.
 */
class PageCache
{
    private const VERSION_KEY = 'public:pagecache:version';

    private const ENTRY_PREFIX = 'public:pagecache:v';

    /**
     * The ONLY query parameters that change what a public page renders:
     * pagination, and the two filters on the projects index. Everything
     * else is dropped from the key, which is an allowlist on purpose
     * rather than a list of tracking parameters to block.
     *
     * Two reasons. A thousand ad clicks carrying distinct gclid values
     * would otherwise be a thousand cache misses - exactly the traffic
     * that has to be cheap - and CaptureLeadAttribution has already read
     * those parameters by the time this runs, so attribution is intact.
     * And a blocklist leaves the cache trivially bypassable: anything
     * crawling /?x=1 upwards would miss every time and put the database
     * back on the critical path, which is the failure this exists to
     * prevent. Add a parameter here when a page starts varying on it.
     *
     * @var list<string>
     */
    private const SIGNIFICANT_QUERY_KEYS = ['page', 'service', 'area'];

    public function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }

    /**
     * Retires every cached page at once. Cheap, and safe to call on every
     * editor save.
     */
    public function flush(): void
    {
        Cache::forever(self::VERSION_KEY, $this->version() + 1);
    }

    /**
     * @return array{content: string, type: string}|null
     */
    public function get(string $key): ?array
    {
        $entry = Cache::get($key);

        return is_array($entry) && isset($entry['content'], $entry['type']) ? $entry : null;
    }

    public function put(string $key, string $content, string $type, int $ttlSeconds): void
    {
        Cache::put($key, ['content' => $content, 'type' => $type], $ttlSeconds);
    }

    /**
     * The cache key for a path + query pair: everything outside the
     * allowlist above is dropped, and what remains is sorted so
     * ?page=2&area=3 and ?area=3&page=2 are one entry.
     *
     * @param  array<string, mixed>  $query
     */
    public function keyFor(string $path, array $query): string
    {
        $query = array_intersect_key($query, array_flip(self::SIGNIFICANT_QUERY_KEYS));

        ksort($query);

        $signature = $path.($query === [] ? '' : '?'.http_build_query($query));

        return self::ENTRY_PREFIX.$this->version().':'.sha1($signature);
    }
}
