<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\ContentBlock;
use App\Models\Faq;
use App\Models\Offer;
use App\Models\Page;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\AssertsNoEmptyState;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

/**
 * Offer Detail's surface is decided by Offer::availability(). These tests
 * pin the three states so a Scheduled or Expired offer can never wear an
 * Active offer's conversion button, and pin that every fact on the page
 * (dates, value line, included services, covered areas) is real data.
 */
class OfferDetailTest extends TestCase
{
    use AssertsNoEmptyState, BuildsSeoFixtures, RefreshDatabase;

    public function test_an_active_offer_shows_the_conversion_cta_and_its_end_date(): void
    {
        $this->publishedOffer('active-detail', starts: now()->subDay(), ends: now()->addDays(10), label: 'قيمة-حقيقية-للعرض');

        $content = $this->get('/offers/active-detail')->assertOk()->getContent();

        $this->assertStringContainsString('عرض متاح الآن', $content);
        $this->assertStringContainsString('اطلب هذا العرض', $content);
        $this->assertStringContainsString('ساري حتى', $content);
        $this->assertStringContainsString('datetime="'.now()->addDays(10)->toDateString().'"', $content);
        // The editor's value line appears exactly once - never repeated as badges.
        $this->assertSame(1, substr_count($content, 'قيمة-حقيقية-للعرض'));
    }

    public function test_a_scheduled_offer_never_masquerades_as_active(): void
    {
        $start = now()->addDays(7);
        $this->publishedOffer('scheduled-detail', starts: $start, ends: now()->addDays(30));

        $content = $this->get('/offers/scheduled-detail')->assertOk()->getContent();

        $this->assertStringContainsString('يبدأ في', $content);
        $this->assertStringContainsString('datetime="'.$start->toDateString().'"', $content);
        $this->assertStringNotContainsString('عرض متاح الآن', $content);
        $this->assertStringNotContainsString('اطلب هذا العرض', $content);
    }

    public function test_an_expired_offer_states_it_ended_and_points_to_current_offers(): void
    {
        $end = now()->subDays(3);
        $this->publishedOffer('expired-detail', starts: now()->subMonth(), ends: $end);

        $content = $this->get('/offers/expired-detail')->assertOk()->getContent();

        $this->assertStringContainsString('انتهى هذا العرض', $content);
        $this->assertStringContainsString('انتهى في', $content);
        $this->assertStringContainsString('datetime="'.$end->toDateString().'"', $content);
        $this->assertStringContainsString(route('public.offers.index'), $content);
        $this->assertStringNotContainsString('اطلب هذا العرض', $content);
        $this->assertStringNotContainsString('ساري حتى', $content);
    }

    public function test_included_services_and_covered_areas_come_only_from_real_relations(): void
    {
        $servicePage = $this->createCompliantServicePage(slug: 'offer-svc');
        $areaPage = $this->createCompliantAreaPage(slug: 'offer-area', title: 'منطقة-مشمولة-بالعرض');
        $unrelatedService = $this->createCompliantServicePage(slug: 'other-svc');

        $offer = $this->publishedOffer('scoped-offer', starts: now()->subDay(), ends: now()->addDays(10));
        $offer->services()->attach($servicePage->pageable);
        $offer->areas()->attach($areaPage->pageable);

        $content = $this->get('/offers/scoped-offer')->assertOk()->getContent();

        $this->assertStringContainsString('يشمل العرض', $content);
        $this->assertStringContainsString('href="'.route('public.service', 'offer-svc').'"', $content);
        $this->assertStringContainsString('متاح في', $content);
        $this->assertStringContainsString('href="'.route('public.area', 'offer-area').'"', $content);
        $this->assertStringNotContainsString('href="'.route('public.service', 'other-svc').'"', $content);

        // No relations: both lists vanish, no empty state.
        $this->publishedOffer('bare-offer', starts: now()->subDay(), ends: now()->addDays(10));
        $bare = $this->get('/offers/bare-offer')->assertOk()->getContent();
        $this->assertStringNotContainsString('يشمل العرض', $bare);
        $this->assertStringNotContainsString('متاح في', $bare);
        $this->assertNoCustomerFacingEmptyState($bare);
    }

    public function test_the_conversion_cta_prefills_the_quote_with_the_first_included_service(): void
    {
        $servicePage = $this->createCompliantServicePage(slug: 'prefill-svc');
        $offer = $this->publishedOffer('prefill-offer', starts: now()->subDay(), ends: now()->addDays(10));
        $offer->services()->attach($servicePage->pageable);

        $content = $this->get('/offers/prefill-offer')->assertOk()->getContent();

        $expected = route('public.quote', ['service' => $servicePage->pageable->id]);
        // Hero and closing band - two, never one per section.
        $this->assertSame(2, substr_count($content, $expected));
    }

    public function test_the_faq_renders_once_and_before_the_decision(): void
    {
        $offer = $this->publishedOffer('faq-offer', starts: now()->subDay(), ends: now()->addDays(10));
        ContentBlock::factory()->for($offer->page)->create(['type' => 'faq', 'position' => 1, 'data' => ['heading' => 'أسئلة عن هذا العرض']]);
        Faq::factory()->for($offer->page)->create(['question' => 'هل يشمل الضريبة؟', 'answer' => 'نعم.']);

        $content = $this->get('/offers/faq-offer')->assertOk()->getContent();
        $body = $this->stripScripts($content);

        $this->assertSame(1, substr_count($body, 'أسئلة عن هذا العرض'));
        $this->assertSame(1, substr_count($body, 'هل يشمل الضريبة؟'));
        $this->assertLessThan(
            mb_strpos($content, 'جاهز للاستفادة من العرض'),
            mb_strpos($content, 'أسئلة عن هذا العرض'),
        );
    }

    public function test_no_countdown_or_scarcity_markup_is_ever_rendered(): void
    {
        $this->publishedOffer('calm-offer', starts: now()->subDay(), ends: now()->addDays(2));

        $content = $this->get('/offers/calm-offer')->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression('/countdown|setInterval|متبقي|فقط اليوم|آخر فرصة|لفترة محدودة/u', $content);
    }

    public function test_the_page_triggers_no_lazy_loading(): void
    {
        $servicePage = $this->createCompliantServicePage(slug: 'lazy-offer-svc');
        $areaPage = $this->createCompliantAreaPage(slug: 'lazy-offer-area');
        $offer = $this->publishedOffer('lazy-offer', starts: now()->subDay(), ends: now()->addDays(10));
        $offer->services()->attach($servicePage->pageable);
        $offer->areas()->attach($areaPage->pageable);

        $violations = [];
        Model::preventLazyLoading(true);
        Model::handleLazyLoadingViolationUsing(function ($model, $relation) use (&$violations) {
            $violations[] = $model::class.'::'.$relation;
        });

        try {
            $this->get('/offers/lazy-offer')->assertOk();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertSame([], $violations);
    }

    private function publishedOffer(string $slug, $starts, $ends, ?string $label = null): Offer
    {
        $offer = Offer::factory()->create([
            'title' => 'عرض '.$slug,
            'discount_label' => $label,
            'is_active' => true,
            'starts_at' => $starts,
            'ends_at' => $ends,
        ]);

        $page = Page::factory()->create([
            'type' => PageType::Offer,
            'title' => 'عرض '.$slug,
            'slug' => $slug,
            'status' => PageStatus::Draft,
        ]);
        $offer->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => '<p>تفاصيل العرض.</p>']]);
        $page->update(['status' => PageStatus::Published]);

        return $offer->fresh(['page']);
    }
}
