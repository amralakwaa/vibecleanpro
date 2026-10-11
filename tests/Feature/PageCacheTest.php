<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use App\Support\Cache\PageCache;
use App\Support\Settings\IntegrationSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The full-page cache trades correctness risk for capacity, so the risks
 * are what is pinned here: a form page must never be stored (its CSRF
 * token belongs to one visitor), a signed-in editor must never be served
 * a stale page, campaign parameters must not fragment the cache, and a
 * content save must retire everything.
 */
class PageCacheTest extends TestCase
{
    use RefreshDatabase;

    private function enableCache(int $ttlMinutes = 10): void
    {
        $settings = app(IntegrationSettings::class);
        $settings->set(IntegrationSettings::PAGE_CACHE_ENABLED, true, 'bool');
        $settings->set(IntegrationSettings::PAGE_CACHE_TTL_MINUTES, $ttlMinutes, 'int');
    }

    public function test_it_does_not_cache_while_disabled(): void
    {
        $this->get('/')->assertOk()->assertHeaderMissing('X-Page-Cache');
    }

    public function test_a_second_visit_is_served_from_cache(): void
    {
        $this->enableCache();

        $this->get('/')->assertOk()->assertHeader('X-Page-Cache', 'MISS');
        $this->get('/')->assertOk()->assertHeader('X-Page-Cache', 'HIT');
    }

    public function test_campaign_parameters_share_one_cache_entry(): void
    {
        $this->enableCache();

        $this->get('/?utm_source=google&gclid=abc')->assertHeader('X-Page-Cache', 'MISS');
        $this->get('/?utm_source=facebook&gclid=zzz')->assertHeader('X-Page-Cache', 'HIT');
    }

    public function test_a_significant_query_parameter_is_still_part_of_the_key(): void
    {
        $this->enableCache();

        $this->get('/?page=1')->assertHeader('X-Page-Cache', 'MISS');
        $this->get('/?page=2')->assertHeader('X-Page-Cache', 'MISS');
        $this->get('/?page=1')->assertHeader('X-Page-Cache', 'HIT');
    }

    public function test_an_unknown_query_parameter_cannot_bypass_the_cache(): void
    {
        $this->enableCache();

        $this->get('/')->assertHeader('X-Page-Cache', 'MISS');

        // A crawler walking ?x=1,2,3... must not put the database back on
        // the critical path - every one of these is the same page.
        foreach (range(1, 5) as $i) {
            $this->get("/?x={$i}")->assertHeader('X-Page-Cache', 'HIT');
        }
    }

    public function test_a_page_carrying_a_csrf_token_is_never_stored(): void
    {
        $this->enableCache();

        $this->get('/quote')->assertOk();
        $this->get('/quote')->assertOk()->assertHeader('X-Page-Cache', 'MISS');
    }

    public function test_a_signed_in_editor_is_never_served_from_cache(): void
    {
        $this->enableCache();

        $this->get('/')->assertHeader('X-Page-Cache', 'MISS');

        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertOk()
            ->assertHeaderMissing('X-Page-Cache');
    }

    public function test_flushing_retires_every_stored_page(): void
    {
        $this->enableCache();

        $this->get('/')->assertHeader('X-Page-Cache', 'MISS');
        $this->get('/')->assertHeader('X-Page-Cache', 'HIT');

        app(PageCache::class)->flush();

        $this->get('/')->assertHeader('X-Page-Cache', 'MISS');
    }

    public function test_saving_content_retires_the_cache(): void
    {
        $this->enableCache();

        $this->get('/')->assertHeader('X-Page-Cache', 'MISS');
        $this->get('/')->assertHeader('X-Page-Cache', 'HIT');

        Service::factory()->create();

        $this->get('/')->assertHeader('X-Page-Cache', 'MISS');
    }

    public function test_a_post_request_is_never_cached(): void
    {
        $this->enableCache();

        $this->post('/e', [])->assertHeaderMissing('X-Page-Cache');
    }
}
