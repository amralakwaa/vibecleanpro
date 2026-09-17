<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\BusinessProfile;
use App\Models\ContentBlock;
use App\Models\Media;
use App\Models\Offer;
use App\Models\Page;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

/**
 * Phase 2 visual contracts for the service page, the offers index and the
 * offer detail: the V2 surfaces follow the real status, the hero action is
 * wired to the sticky bar, pictures are the entity's own media (or the
 * covered service's) from local storage, and the inclusions split keeps
 * both sides legible without inventing anything.
 */
class ServiceAndOffersV2Test extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    public function test_the_service_hero_states_the_price_once_and_carries_the_sticky_bar_hook(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'whatsapp_number' => '+966500000000']);
        $service = $this->createCompliantServicePage(slug: 'v2-svc')->pageable;
        $service->update(['pricing_mode' => 'starting_from', 'price_min' => 899, 'price_note' => 'ملاحظة-السعر']);
        $this->offer('v2-active', ['discount_label' => 'قيمة-العرض', 'offer_price' => 750, 'starts_at' => now()->subDay(), 'ends_at' => now()->addWeek()], [$service]);

        $html = $this->get('/services/v2-svc')->assertOk()->getContent();
        preg_match('/<main[\s\S]*<\/main>/u', $html, $main);

        $this->assertStringContainsString('data-hero-cta', $html);
        $this->assertSame(1, substr_count($main[0], 'يبدأ من 899 ر.س'), 'the service price is stated once');
        $this->assertStringContainsString('ملاحظة-السعر', $html);
        // The hero offer cue links to the offer and never repeats numbers; the offer moment owns them.
        $this->assertMatchesRegularExpression('/<a href="'.preg_quote(route('public.offer', 'v2-active'), '/').'"[^>]*>\s*<span[^>]*>عرض متاح<\/span>/u', $html);
        $this->assertStringContainsString('750 ر.س', $html);
        $this->assertStringNotContainsString('بدلًا من', $html);
        $this->assertStringContainsString('surface-offer', $html);
    }

    public function test_the_inclusions_split_shows_both_sides_with_their_own_cues(): void
    {
        $page = $this->createCompliantServicePage(slug: 'split-svc');
        ContentBlock::factory()->for($page)->create(['type' => 'inclusions', 'position' => 5, 'data' => [
            'heading' => 'عنوان-الشمول',
            'included' => [['item' => 'بند-مشمول']],
            'excluded' => [['item' => 'بند-مستثنى']],
        ]]);

        $html = $this->get('/services/split-svc')->assertOk()->getContent();

        $this->assertLessThan(mb_strpos($html, 'بند-مستثنى'), mb_strpos($html, 'بند-مشمول'));
        // The included list sits on its own tinted panel, the excluded list on a white one.
        $this->assertMatchesRegularExpression('/surface-tint relative overflow-hidden rounded-3xl[^>]*>[\s\S]{0,2500}ما تشمله الخدمة[\s\S]{0,1500}بند-مشمول/u', $html);
        $this->assertMatchesRegularExpression('/rounded-3xl bg-white[^>]*>[\s\S]{0,2500}ما لا تشمله[\s\S]{0,1500}بند-مستثنى/u', $html);
    }

    public function test_the_offers_index_borrows_the_covered_services_photograph_from_local_storage(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro']);
        $service = $this->createCompliantServicePage(slug: 'pictured-svc')->pageable;
        $service->update(['pricing_mode' => 'fixed', 'price_min' => 400]);
        $lead = $this->offer('lead-offer', ['offer_price' => 300, 'starts_at' => now()->subDay(), 'ends_at' => now()->addWeek(), 'featured_media_id' => null], [$service]);
        $this->offer('coming-offer', ['starts_at' => now()->addWeek(), 'ends_at' => now()->addMonth()], [$service]);

        $html = $this->get('/offers')->assertOk()->getContent();

        $this->assertStringContainsString('src="'.$service->featuredMedia->url().'"', $html);
        $this->assertMatchesRegularExpression('/بدلًا من <s[^>]*>400 ر\.س<\/s>/u', $html);
        $this->assertStringContainsString('surface-offer', $html);
        $this->assertLessThan(mb_strpos($html, 'قريبًا'), mb_strpos($html, 'متاح الآن'));
        $this->assertDoesNotMatchRegularExpression('#<img[^>]+src="https?://(?!'.preg_quote(parse_url(config('app.url'), PHP_URL_HOST), '#').')#u', $html);
        $this->assertStringNotContainsString('اطلب هذا العرض', $html);
    }

    public function test_the_offer_detail_surface_follows_the_real_status(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'whatsapp_number' => '+966500000000']);
        $service = $this->createCompliantServicePage(slug: 'status-svc')->pageable;
        $this->offer('status-active', ['starts_at' => now()->subDay(), 'ends_at' => now()->addWeek()], [$service]);
        $this->offer('status-scheduled', ['starts_at' => now()->addWeek(), 'ends_at' => now()->addMonth()], [$service]);
        $this->offer('status-expired', ['starts_at' => now()->subMonth(), 'ends_at' => now()->subDay()], [$service]);

        $active = $this->get('/offers/status-active')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/<section class="[^"]*surface-offer[^"]*">/u', $active);
        $this->assertSame(2, substr_count($active, 'اطلب هذا العرض'), 'hero and decision, nothing in between');
        $this->assertStringContainsString('src="'.$service->featuredMedia->url().'"', $active, 'the covered service photo stands in for a missing offer picture');

        $scheduled = $this->get('/offers/status-scheduled')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/<section class="[^"]*surface-tint[^"]*">/u', $scheduled);
        $this->assertStringNotContainsString('surface-offer', $scheduled);
        $this->assertStringNotContainsString('اطلب هذا العرض', $scheduled);

        $expired = $this->get('/offers/status-expired')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/<section class="[^"]*bg-neutral-100[^"]*">/u', $expired);
        $this->assertStringNotContainsString('surface-offer', $expired);
        $this->assertStringNotContainsString('اطلب هذا العرض', $expired);
        $this->assertStringContainsString('href="'.route('public.offers.index').'"', $expired);
    }

    /**
     * @param  array<int, Service>  $services
     */
    private function offer(string $slug, array $attributes, array $services): Offer
    {
        $offer = Offer::factory()->create([...$attributes, 'title' => 'عرض '.$slug, 'is_active' => true]);
        $offer->services()->attach(collect($services)->pluck('id'));
        $page = Page::factory()->create(['type' => PageType::Offer, 'title' => 'عرض '.$slug, 'slug' => $slug, 'status' => PageStatus::Draft]);
        $offer->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => '<p>شروط.</p>']]);
        $page->update(['status' => PageStatus::Published]);

        return $offer;
    }
}
