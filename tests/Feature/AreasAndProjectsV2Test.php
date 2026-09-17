<?php

namespace Tests\Feature;

use App\Enums\MediaStage;
use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Area;
use App\Models\AreaGroup;
use App\Models\BusinessProfile;
use App\Models\ContentBlock;
use App\Models\Media;
use App\Models\Page;
use App\Models\Project;
use Database\Seeders\InitialMediaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\AssertsNoEmptyState;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

/**
 * Phase 3 visual contracts: the area page stays typographic and never
 * composes a local sentence, the projects index and case study show real
 * project media only (never the stock library), and every honest fallback
 * renders without a placeholder or an empty state.
 */
class AreasAndProjectsV2Test extends TestCase
{
    use AssertsNoEmptyState, BuildsSeoFixtures, RefreshDatabase;

    public function test_the_area_hero_prints_group_and_city_as_facts_and_never_a_composed_local_sentence(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'city' => 'مدينة-من-الإدارة', 'whatsapp_number' => '+966500000000']);
        $page = $this->createCompliantAreaPage(slug: 'v2-area');
        $area = $page->pageable;
        $area->update(['name' => 'حي-الاختبار', 'area_group_id' => AreaGroup::factory()->create(['name' => 'مجموعة-من-الإدارة'])->id]);

        $html = $this->get('/areas/v2-area')->assertOk()->getContent();
        preg_match('/<main[\s\S]*<\/main>/u', $html, $main);

        $this->assertStringContainsString('data-hero-cta', $html);
        $this->assertStringContainsString('مجموعة-من-الإدارة', $html);
        $this->assertStringContainsString('مدينة-من-الإدارة', $html);
        // The area name appears in the CMS title (H1) and exactly once more,
        // as natural copy in the closing question - never repeated across
        // headings, never stuffed into body copy.
        $this->assertStringContainsString('تحتاج خدمة تنظيف في حي-الاختبار؟', $main[0]);
        $this->assertSame(1, preg_match_all('/<h[2-6][^>]*>[^<]*حي-الاختبار/u', $main[0]), 'one closing heading only');
        $this->assertDoesNotMatchRegularExpression('/أفضل شركة|شركة تنظيف حي-الاختبار|بالرياض حي-الاختبار/u', $main[0]);
        $this->assertStringNotContainsString('<img', $this->heroOf($main[0]), 'the local hero is typographic - no picture pretends to be the neighbourhood');
        $this->assertNoCustomerFacingEmptyState($html);
    }

    public function test_the_area_page_lists_services_as_image_led_rows_with_public_prices_only(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro']);
        $areaPage = $this->createCompliantAreaPage(slug: 'priced-area');
        $priced = $this->createCompliantServicePage(slug: 'row-priced')->pageable;
        $priced->update(['name' => 'خدمة-مسعّرة', 'pricing_mode' => 'fixed', 'price_min' => 450]);
        $quote = $this->createCompliantServicePage(slug: 'row-quote')->pageable;
        $quote->update(['name' => 'خدمة-بلا-سعر', 'pricing_mode' => 'quote_only']);
        $areaPage->pageable->services()->attach([$priced->id => ['is_active' => true], $quote->id => ['is_active' => true]]);

        $html = $this->get('/areas/priced-area')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<a href="'.preg_quote(route('public.service', 'row-priced'), '/').'"[\s\S]{0,900}?src="'.preg_quote($priced->featuredMedia->url(), '/').'"[\s\S]{0,900}?خدمة-مسعّرة[\s\S]{0,300}?450 ر\.س/u', $html);
        $this->assertMatchesRegularExpression('/خدمة-بلا-سعر<\/span>\s*<\/span>/u', $html, 'quote-only rows carry no price and no placeholder');
        $this->assertDoesNotMatchRegularExpression('/السعر حسب الطلب|اطلب عرض سعر<\/span>/u', $html);
    }

    public function test_the_projects_index_uses_real_media_tiles_and_a_typographic_panel_for_the_rest(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro']);
        Storage::fake('public');
        $this->seed(InitialMediaSeeder::class);

        $pictured = $this->publishedProject('pictured-project', ['title' => 'مشروع-مصوّر', 'is_featured' => false]);
        $photo = Media::factory()->create(['alt_text' => 'صورة-حقيقية-بعد']);
        $pictured->media()->attach($photo->id, ['stage' => MediaStage::After->value, 'sort_order' => 0]);
        $pictured->media()->attach(Media::factory()->create()->id, ['stage' => MediaStage::Before->value, 'sort_order' => 0]);
        $this->publishedProject('plain-project', ['title' => 'مشروع-بلا-صورة', 'summary' => 'ملخص-حقيقي', 'is_featured' => false]);

        $html = $this->get('/projects')->assertOk()->getContent();

        $this->assertStringContainsString('src="'.$photo->url().'"', $html);
        $this->assertStringContainsString('tile-scrim', $html);
        $this->assertMatchesRegularExpression('/surface-tint[^>]*>[\s\S]{0,1200}مشروع-بلا-صورة[\s\S]{0,400}ملخص-حقيقي/u', $html, 'a project without a photograph gets the typographic panel');
        $this->assertStringNotContainsString('media/library/', $html, 'the stock library never stands in for project work');
        $this->assertDoesNotMatchRegularExpression('/placeholder|bg-neutral-100 overflow-hidden/u', $html);
        $this->assertNoCustomerFacingEmptyState($html);
    }

    public function test_the_projects_index_pre_launch_state_is_respectful_and_claims_nothing(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'whatsapp_number' => '+966500000000']);

        $html = $this->get('/projects')->assertOk()->getContent();

        $this->assertStringContainsString('نجهّز معرض أعمالنا', $html);
        $this->assertDoesNotMatchRegularExpression('/مئات|آلاف|\d+\+? مشروع|عملاء راضون/u', $html);
        $this->assertStringNotContainsString('media/library/', $html);
    }

    public function test_the_case_study_leads_with_the_real_result_photo_and_facts_and_never_invents_a_stage(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'whatsapp_number' => '+966500000000']);
        Storage::fake('public');
        $this->seed(InitialMediaSeeder::class);
        $areaPage = $this->createCompliantAreaPage(slug: 'case-area');
        $service = $this->createCompliantServicePage(slug: 'case-svc')->pageable;
        $project = $this->publishedProject('case-study', ['title' => 'دراسة-الحالة', 'area_id' => $areaPage->pageable->id, 'completed_at' => '2026-03-01', 'summary' => 'خلاصة-من-الإدارة']);
        $project->services()->attach($service);
        $after = Media::factory()->create(['alt_text' => 'النتيجة-الحقيقية']);
        $project->media()->attach($after->id, ['stage' => MediaStage::After->value, 'sort_order' => 0]);

        $html = $this->get('/projects/case-study')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertStringContainsString('data-hero-cta', $html);
        $this->assertMatchesRegularExpression('/src="'.preg_quote($after->url(), '/').'"[^>]*fetchpriority="high"/u', $html);
        $this->assertStringContainsString('خلاصة-من-الإدارة', $html);
        $this->assertMatchesRegularExpression('/<dt[^>]*>الخدمة<\/dt>[\s\S]{0,300}href="'.preg_quote(route('public.service', 'case-svc'), '/').'"/u', $html);
        $this->assertMatchesRegularExpression('/<dt[^>]*>المنطقة<\/dt>[\s\S]{0,300}href="'.preg_quote(route('public.area', 'case-area'), '/').'"/u', $html);
        $this->assertStringContainsString('<time datetime="2026-03-01">', $html);
        // After-only: a result, never a fabricated "before" beside it.
        $this->assertStringContainsString('النتيجة', $html);
        $this->assertStringNotContainsString('قبل وبعد', $html);
        $this->assertStringNotContainsString('media/library/', $html);
        $this->assertDoesNotMatchRegularExpression('/رضا العميل|نسبة|%\s*تحسن|تقييم/u', $html);
    }

    public function test_a_case_study_without_media_keeps_a_clean_typographic_opening(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro']);
        $this->publishedProject('bare-study', ['title' => 'مشروع-بلا-وسائط']);

        $html = $this->get('/projects/bare-study')->assertOk()->getContent();

        $this->assertStringNotContainsString('fetchpriority="high"', $html);
        $this->assertStringNotContainsString('الدليل', $html);
        $this->assertStringNotContainsString('media/library/', $html);
        $this->assertNoCustomerFacingEmptyState($html);
    }

    private function heroOf(string $main): string
    {
        return mb_substr($main, 0, mb_strpos($main, '</section>') ?: null);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function publishedProject(string $slug, array $attributes = []): Project
    {
        $project = Project::factory()->create($attributes);
        $page = Page::factory()->create(['type' => PageType::Project, 'slug' => $slug, 'title' => $attributes['title'] ?? 'مشروع '.$slug, 'status' => PageStatus::Draft]);
        $project->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => '<p>نطاق العمل.</p>']]);
        $page->update(['status' => PageStatus::Published]);

        return $project;
    }
}
