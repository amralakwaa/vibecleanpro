<?php

namespace Tests\Feature;

use App\Enums\MediaStage;
use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\BusinessProfile;
use App\Models\ContentBlock;
use App\Models\Media;
use App\Models\Offer;
use App\Models\Page;
use App\Models\Project;
use App\Models\Service;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

/**
 * Homepage V2 contracts: the visual reset changed how things look, not
 * where facts come from. Every number, price, offer and identity line
 * is CMS data; a service without a photo still gets a designed tile;
 * the sticky bar knows how to step aside for the hero action.
 */
class HomepageV2Test extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    public function test_the_hero_carries_one_h1_the_primary_action_and_real_service_chips(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'city' => 'مدينة-من-الإدارة', 'tagline' => 'وصف-من-الإدارة', 'whatsapp_number' => '+966500000000']);
        $priced = $this->createCompliantServicePage(slug: 'chip-priced')->pageable;
        $priced->update(['name' => 'خدمة-مسعّرة', 'pricing_mode' => 'starting_from', 'price_min' => 299, 'is_featured' => true]);
        $quote = $this->createCompliantServicePage(slug: 'chip-quote')->pageable;
        $quote->update(['name' => 'خدمة-بلا-سعر']);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertStringContainsString('data-hero-cta', $html);
        $this->assertStringContainsString('وصف-من-الإدارة', $html);
        $this->assertStringContainsString('مدينة-من-الإدارة', $html);
        // Chips are real links; the price rides along only when one exists.
        $this->assertMatchesRegularExpression('/<a href="'.preg_quote(route('public.service', 'chip-priced'), '/').'"[^>]*>\s*<span>خدمة-مسعّرة<\/span>\s*<span[^>]*>يبدأ من 299 ر\.س<\/span>/u', $html);
        $this->assertMatchesRegularExpression('/<a href="'.preg_quote(route('public.service', 'chip-quote'), '/').'"[^>]*>\s*<span>خدمة-بلا-سعر<\/span>\s*<\/a>/u', $html);
        // The hero action and the sticky bar are wired to each other.
        $this->assertStringContainsString("document.querySelector('[data-hero-cta]')", $html);
    }

    public function test_hero_facts_appear_only_from_published_counts(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'city' => 'الرياض']);

        $this->assertStringNotContainsString('مناطق التغطية</dt>', $this->get('/')->getContent());

        foreach (['fa', 'fb', 'fc'] as $slug) {
            $this->createCompliantAreaPage(slug: 'fact-'.$slug);
        }
        $this->createCompliantAreaPage(slug: 'fact-draft', status: PageStatus::Draft);

        $html = $this->get('/')->getContent();
        $this->assertStringContainsString('مناطق التغطية</dt>', $html);
        $this->assertStringContainsString('3 أحياء', $html);
    }

    public function test_service_tiles_lead_with_the_featured_service_and_never_show_a_grey_placeholder(): void
    {
        $featured = $this->createCompliantServicePage(slug: 'tile-featured')->pageable;
        $featured->update(['name' => 'خدمة-مميزة-البلاطة', 'is_featured' => true, 'pricing_mode' => 'fixed', 'price_min' => 350]);
        $photoless = $this->createCompliantServicePage(slug: 'tile-photoless')->pageable;
        $photoless->update(['name' => 'خدمة-بلا-صورة', 'featured_media_id' => null]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertLessThan(mb_strpos($html, 'خدمة-بلا-صورة'), mb_strpos($html, 'خدمة-مميزة-البلاطة'));
        $this->assertStringContainsString('350 ر.س', $html);
        // The photo-less tile gets the lit blue fallback, not a grey box.
        $this->assertMatchesRegularExpression('/surface-offer[^>]*>\s*<span[^>]*>خدمة-بلا-صورة<\/span>/u', $html);
        $this->assertStringNotContainsString('bg-neutral-100 overflow-hidden', $html);
        $this->assertStringNotContainsString('اطلب الخدمة</span>', $html);
    }

    public function test_the_offer_moment_states_only_admin_numbers_and_real_status(): void
    {
        $service = $this->createCompliantServicePage(slug: 'offer-home-svc')->pageable;
        $service->update(['name' => 'خدمة-العرض', 'pricing_mode' => 'fixed', 'price_min' => 399]);

        $this->offer('home-active', ['offer_price' => 299, 'discount_label' => 'قيمة-العرض', 'starts_at' => now()->subDay(), 'ends_at' => now()->addWeek()], [$service]);
        $this->offer('home-scheduled', ['starts_at' => now()->addWeek(), 'ends_at' => now()->addMonth()], [$service]);
        $this->offer('home-expired', ['starts_at' => now()->subMonth(), 'ends_at' => now()->subDay()], [$service]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('عرض متاح الآن', $html);
        $this->assertStringContainsString('قيمة-العرض', $html);
        $this->assertStringContainsString('299 ر.س', $html);
        $this->assertMatchesRegularExpression('/بدلًا من <s[^>]*>399 ر\.س<\/s>/u', $html);
        $this->assertStringContainsString('href="'.route('public.quote', ['service' => $service->id]).'"', $html);
        $this->assertStringContainsString('قريبًا', $html);
        $this->assertStringContainsString('عرض home-scheduled', $html);
        $this->assertStringNotContainsString('عرض home-expired', $html);
        $this->assertDoesNotMatchRegularExpression('/توفير|متبقي|countdown|%\s*خصم/u', $html);
    }

    public function test_trust_uses_cms_principles_and_the_visible_founder_only(): void
    {
        $profile = BusinessProfile::query()->create([
            'name' => 'Vibe Clean Pro',
            'identity_statement' => 'هوية-من-الإدارة',
            'values' => [['title' => 'مبدأ-من-الإدارة', 'description' => 'شرح-المبدأ']],
            'founder_name' => 'مؤسس-من-الإدارة',
            'founder_title' => 'صفة-المؤسس',
        ]);
        Testimonial::factory()->create(['author_name' => 'عميل-حقيقي', 'content' => 'رأي-حقيقي']);

        $html = $this->get('/')->assertOk()->getContent();
        foreach (['هوية-من-الإدارة', 'مبدأ-من-الإدارة', 'شرح-المبدأ', 'صفة-المؤسس: مؤسس-من-الإدارة', 'رأي-حقيقي', 'عميل-حقيقي'] as $text) {
            $this->assertStringContainsString($text, $html);
        }

        $profile->update(['show_founder' => false, 'values' => []]);
        $bare = $this->get('/')->getContent();
        $this->assertStringNotContainsString('مؤسس-من-الإدارة', $bare);
        $this->assertStringNotContainsString('مبدأ-من-الإدارة', $bare);
        $this->assertDoesNotMatchRegularExpression('/الأكوع|آلاف|عملاء راضون|\d+ عميل|ضمان 100/u', $bare);
    }

    public function test_the_homepage_triggers_no_lazy_loading_with_every_section_populated(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'city' => 'الرياض', 'whatsapp_number' => '+966500000000', 'phone' => '+966500000000', 'values' => [['title' => 'مبدأ']], 'founder_name' => 'مؤسس']);
        $services = collect(['h1', 'h2', 'h3'])->map(fn ($s) => $this->createCompliantServicePage(slug: 'lazy-'.$s)->pageable);
        $services->first()->update(['pricing_mode' => 'fixed', 'price_min' => 300, 'is_featured' => true]);
        foreach (['la', 'lb', 'lc'] as $slug) {
            $this->createCompliantAreaPage(slug: 'lazy-area-'.$slug);
        }
        foreach (range(1, 3) as $i) {
            $project = Project::factory()->create(['title' => 'مشروع-'.$i, 'is_featured' => $i === 1]);
            $project->services()->attach($services->first());
            $project->media()->attach(Media::factory()->create()->id, ['stage' => MediaStage::Before->value, 'sort_order' => 0]);
            $project->media()->attach(Media::factory()->create()->id, ['stage' => MediaStage::After->value, 'sort_order' => 0]);
            $page = Page::factory()->create(['type' => PageType::Project, 'slug' => 'lazy-project-'.$i]);
            $project->page()->save($page);
            ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'نص.']]);
            $page->update(['status' => PageStatus::Published]);
        }
        $this->offer('lazy-offer', ['offer_price' => 250, 'starts_at' => now()->subDay(), 'ends_at' => now()->addWeek()], [$services->first()]);
        Testimonial::factory()->create();

        $violations = [];
        Model::preventLazyLoading(true);
        Model::handleLazyLoadingViolationUsing(function ($model, $relation) use (&$violations) {
            $violations[] = $model::class.'::'.$relation;
        });

        try {
            $this->get('/')->assertOk();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertSame([], $violations);
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
