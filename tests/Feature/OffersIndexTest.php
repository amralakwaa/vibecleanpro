<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\ContentBlock;
use App\Models\Offer;
use App\Models\Page;
use App\Models\SeoMetadata;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OffersIndexTest extends TestCase
{
    use RefreshDatabase;

    private function createPublishedOffer(string $slug, array $overrides = []): Offer
    {
        $offer = Offer::factory()->create([...$overrides, 'title' => $slug]);
        $page = Page::factory()->create(['type' => PageType::Offer, 'slug' => $slug]);
        $offer->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'نص.']]);
        SeoMetadata::factory()->for($page)->create();
        $page->update(['status' => PageStatus::Published]);

        return $offer;
    }

    public function test_an_active_offer_is_listed(): void
    {
        $this->createPublishedOffer('active-offer', ['is_active' => true, 'starts_at' => now()->subDay(), 'ends_at' => now()->addWeek()]);

        $this->get('/offers')->assertSee('active-offer');
    }

    public function test_an_expired_offer_is_never_listed(): void
    {
        $this->createPublishedOffer('expired-offer', ['is_active' => true, 'ends_at' => now()->subDay()]);

        $this->get('/offers')->assertDontSee('expired-offer');
    }

    public function test_a_manually_disabled_offer_is_never_listed_regardless_of_its_dates(): void
    {
        $this->createPublishedOffer('disabled-offer', ['is_active' => false, 'ends_at' => now()->addMonth()]);

        $this->get('/offers')->assertDontSee('disabled-offer');
    }

    public function test_a_scheduled_future_offer_is_listed_but_marked_as_upcoming(): void
    {
        $this->createPublishedOffer('scheduled-offer', ['is_active' => true, 'starts_at' => now()->addWeek()]);

        $response = $this->get('/offers');

        $response->assertSee('scheduled-offer');
        $response->assertSee('قريبًا');
    }

    public function test_an_expired_offers_own_page_stays_reachable_but_never_shows_a_misleading_cta(): void
    {
        $this->createPublishedOffer('expired-but-reachable', ['is_active' => true, 'ends_at' => now()->subWeek()]);

        $response = $this->get('/offers/expired-but-reachable');

        $response->assertOk();
        $response->assertSee('انتهى هذا العرض');
        $response->assertSee('انتهى العرض');
    }

    public function test_the_index_shows_an_empty_state_when_nothing_is_currently_available(): void
    {
        $this->createPublishedOffer('only-expired', ['is_active' => true, 'ends_at' => now()->subDay()]);

        $response = $this->get('/offers');

        $response->assertOk();
        $response->assertSee('لا توجد عروض متاحة حاليًا');
    }
}
