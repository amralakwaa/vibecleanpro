<?php

namespace Tests\Feature;

use App\Seo\SeoDashboardService;
use App\Seo\ValueObjects\SeoDashboardSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * config/cache.php sets 'serializable_classes' => false, Laravel's hardened
 * default: no PHP object may be unserialized back out of the cache, so a
 * leaked APP_KEY cannot be turned into a gadget chain.
 *
 * The SEO dashboard used to cache its SeoDashboardSummary object directly.
 * The first read computed and stored it and looked fine; every read for the
 * next ten minutes got __PHP_Incomplete_Class back, which summary()'s return
 * type rejected with a TypeError, so the widget answered HTTP 500 until the
 * entry expired. These tests pin the shape that avoids it.
 */
class SeoDashboardCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_summary_survives_a_warm_cache(): void
    {
        $service = app(SeoDashboardService::class);

        $first = $service->summary();
        $second = $service->summary();

        $this->assertInstanceOf(SeoDashboardSummary::class, $first);
        $this->assertInstanceOf(SeoDashboardSummary::class, $second);
        $this->assertEquals($first, $second);
    }

    public function test_nothing_but_plain_data_is_written_to_the_cache(): void
    {
        app(SeoDashboardService::class)->summary();

        $cached = Cache::get('seo:dashboard-summary');

        $this->assertIsArray($cached, 'The dashboard must cache plain data, never an object.');
        $this->assertSame([], array_filter($cached, 'is_object'));
    }

    public function test_the_cached_counts_rebuild_the_whole_value_object(): void
    {
        $service = app(SeoDashboardService::class);
        $service->summary();

        // Named-argument spreading is what reconstructs the object, so a key
        // renamed on one side and not the other has to fail loudly here
        // rather than at runtime in the panel.
        $summary = new SeoDashboardSummary(...Cache::get('seo:dashboard-summary'));

        $this->assertEquals($service->summary(), $summary);
    }

    public function test_forgetting_the_cache_recomputes(): void
    {
        $service = app(SeoDashboardService::class);
        $service->summary();

        SeoDashboardService::forgetCache();

        $this->assertNull(Cache::get('seo:dashboard-summary'));
        $this->assertInstanceOf(SeoDashboardSummary::class, $service->summary());
    }
}
