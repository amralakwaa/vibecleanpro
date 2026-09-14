<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\ContentBlock;
use App\Models\Offer;
use App\Models\Page;
use App\Models\SeoMetadata;
use Illuminate\Database\Eloquent\Model;
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
        // The active-offer conversion label must never appear on an
        // expired offer's page.
        $response->assertDontSee('اطلب هذا العرض');
    }

    public function test_the_index_shows_an_empty_state_when_nothing_is_currently_available(): void
    {
        $this->createPublishedOffer('only-expired', ['is_active' => true, 'ends_at' => now()->subDay()]);

        $response = $this->get('/offers');

        $response->assertOk();
        $response->assertSee('لا توجد عروض متاحة حاليًا');
    }

    public function test_active_offers_lead_and_scheduled_offers_sit_below_them(): void
    {
        // Scheduled offer is given the LOWER sort_order so that only the
        // status partition - not sort_order - can put it below the active one.
        $this->createPublishedOffer('later-scheduled', ['is_active' => true, 'sort_order' => 0, 'starts_at' => now()->addWeek(), 'ends_at' => now()->addMonth()]);
        $this->createPublishedOffer('first-active', ['is_active' => true, 'sort_order' => 5, 'starts_at' => now()->subDay(), 'ends_at' => now()->addWeek()]);

        $html = $this->get('/offers')->assertOk()->getContent();

        $this->assertStringContainsString('متاح الآن', $html);
        $this->assertStringContainsString('قريبًا', $html);
        $this->assertLessThan(mb_strpos($html, 'later-scheduled'), mb_strpos($html, 'first-active'));
        $this->assertLessThan(mb_strpos($html, 'قريبًا'), mb_strpos($html, 'متاح الآن'));
    }

    public function test_the_lead_offer_is_not_repeated_in_the_rows_below(): void
    {
        $this->createPublishedOffer('lead-only-once', ['is_active' => true, 'starts_at' => now()->subDay(), 'ends_at' => now()->addWeek()]);
        $this->createPublishedOffer('second-active', ['is_active' => true, 'sort_order' => 2, 'starts_at' => now()->subDay(), 'ends_at' => now()->addWeek()]);

        $html = $this->get('/offers')->assertOk()->getContent();

        // Count the title as rendered text (between tags, whitespace
        // tolerant) - the slug also appears once inside the href, which
        // must not be counted as a second listing.
        $this->assertSame(1, preg_match_all('/>\s*lead-only-once\s*</u', $html));
        $this->assertStringContainsString('عروض أخرى متاحة', $html);
        $this->assertStringContainsString('second-active', $html);
    }

    public function test_the_index_triggers_no_lazy_loading(): void
    {
        foreach (range(1, 3) as $i) {
            $this->createPublishedOffer("lazy-$i", ['is_active' => true, 'sort_order' => $i, 'starts_at' => now()->subDay(), 'ends_at' => now()->addWeek()]);
        }
        $this->createPublishedOffer('lazy-scheduled', ['is_active' => true, 'sort_order' => 9, 'starts_at' => now()->addWeek(), 'ends_at' => now()->addMonth()]);

        $violations = [];
        Model::preventLazyLoading(true);
        Model::handleLazyLoadingViolationUsing(function ($model, $relation) use (&$violations) {
            $violations[] = $model::class.'::'.$relation;
        });

        try {
            $this->get('/offers')->assertOk();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertSame([], $violations);
    }
}
