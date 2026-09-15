<?php

namespace Tests\Feature;

use App\Enums\MediaStage;
use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Area;
use App\Models\ContentBlock;
use App\Models\Faq;
use App\Models\Media;
use App\Models\Offer;
use App\Models\Page;
use App\Models\Project;
use App\Models\SeoMetadata;
use App\Models\Service;
use App\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

class ServiceDetailTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    public function test_areas_served_and_projects_sections_are_hidden_when_the_service_has_none(): void
    {
        $service = Service::factory()->create();
        $page = Page::factory()->create(['type' => PageType::Service, 'slug' => 'lonely-service']);
        $service->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'نص كافٍ لهذه الخدمة.']]);
        SeoMetadata::factory()->for($page)->create();
        $page->update(['status' => PageStatus::Published]);

        $response = $this->get('/services/lonely-service');

        $response->assertOk();
        $response->assertDontSee('نقدّم هذه الخدمة في المناطق التالية');
        $response->assertDontSee('مشاريع منفذة لهذه الخدمة');
    }

    public function test_areas_served_shows_only_areas_with_a_published_page(): void
    {
        $page = $this->createCompliantServicePage(slug: 'service-with-areas');
        $service = $page->pageable;

        // A second, genuinely published Area - this one must appear.
        $publishedArea = Area::factory()->create(['name' => 'منطقة-منشورة-فعليًا']);
        $areaPage = Page::factory()->create(['type' => PageType::Area, 'slug' => 'published-area-for-service-test']);
        $publishedArea->page()->save($areaPage);
        ContentBlock::factory()->for($areaPage)->create(['type' => 'rich_text', 'data' => ['content' => 'نص.']]);
        $areaPage->update(['status' => PageStatus::Published]);
        $service->areas()->attach($publishedArea);

        // An unpublished Area attached to the same Service must never
        // appear, even though the section itself now renders.
        $unpublishedArea = Area::factory()->create(['name' => 'منطقة-غير-منشورة']);
        $service->areas()->attach($unpublishedArea);

        $response = $this->get('/services/service-with-areas');

        $response->assertSee('منطقة-منشورة-فعليًا');
        $response->assertDontSee('منطقة-غير-منشورة');
    }

    /**
     * Exercises the blocks renderer's own only/except contract, with the
     * FAQ data deliberately supplied to BOTH passes - that is the case
     * the page itself cannot reproduce (it only hands $faqs to the second
     * pass), so without this the type filtering would be untested.
     */
    /**
     * The related_content block is the one real path that renders
     * service-card. is_featured is an editor toggle ("خدمة مميزة" in the
     * admin), not demand data - so the card must say exactly that and
     * never dress it up as popularity.
     */
    public function test_the_related_block_lists_real_services_as_links_without_invented_labels(): void
    {
        $page = $this->createCompliantServicePage(slug: 'service-related-cards');
        ContentBlock::factory()->for($page)->create(['type' => 'related_content', 'position' => 1, 'data' => ['heading' => 'خدمات ذات صلة']]);
        $featured = $this->createCompliantServicePage(slug: 'featured-related')->pageable;
        $featured->update(['name' => 'خدمة-مميزة-فعلًا', 'is_featured' => true, 'pricing_mode' => 'starting_from', 'price_min' => 250]);
        $plain = $this->createCompliantServicePage(slug: 'plain-related')->pageable;
        $plain->update(['name' => 'خدمة-عادية-تمامًا', 'is_featured' => false]);

        $html = Blade::render(
            '<x-public.blocks :blocks="$blocks" :related="$related" related-item-type="service" />',
            ['blocks' => $page->fresh(['contentBlocks'])->contentBlocks, 'related' => collect([$featured->fresh(['page', 'featuredMedia']), $plain->fresh(['page', 'featuredMedia'])])],
        );

        $this->assertStringContainsString('href="'.route('public.service', 'featured-related').'"', $html);
        $this->assertStringContainsString('href="'.route('public.service', 'plain-related').'"', $html);
        $this->assertStringContainsString('خدمات ذات صلة', $html);
        // The admin's price travels with the link; nothing about popularity is claimed.
        $this->assertStringContainsString('يبدأ من 250 ر.س', $html);
        $this->assertStringNotContainsString('الأكثر طلبًا', $html);
    }

    public function test_blocks_renderer_filters_by_type_so_two_passes_never_overlap(): void
    {
        $page = $this->createCompliantServicePage(slug: 'service-blocks-filter');
        ContentBlock::factory()->for($page)->create(['type' => 'features', 'position' => 1, 'data' => ['heading' => 'عنوان المزايا', 'items' => [['title' => 'بند ظاهر']]]]);
        ContentBlock::factory()->for($page)->create(['type' => 'faq', 'position' => 2, 'data' => ['heading' => 'عنوان الأسئلة']]);
        Faq::factory()->for($page)->create(['question' => 'سؤال مفلتر؟', 'answer' => 'إجابة مفلترة.']);

        $page = $page->fresh(['contentBlocks']);
        $faqs = $page->faqs()->where('is_active', true)->get();

        $exceptPass = Blade::render(
            '<x-public.blocks :blocks="$blocks" :faqs="$faqs" :except="[\'faq\']" />',
            ['blocks' => $page->contentBlocks, 'faqs' => $faqs],
        );

        $onlyPass = Blade::render(
            '<x-public.blocks :blocks="$blocks" :faqs="$faqs" :only="[\'faq\']" />',
            ['blocks' => $page->contentBlocks, 'faqs' => $faqs],
        );

        // except: keeps the other blocks, drops the FAQ entirely - even
        // though the FAQ data was handed to it.
        $this->assertStringContainsString('عنوان المزايا', $exceptPass);
        $this->assertStringNotContainsString('عنوان الأسئلة', $exceptPass);
        $this->assertStringNotContainsString('سؤال مفلتر؟', $exceptPass);

        // only: the mirror image - the FAQ and nothing else.
        $this->assertSame(1, substr_count($onlyPass, 'عنوان الأسئلة'));
        $this->assertSame(1, substr_count($onlyPass, 'سؤال مفلتر؟'));
        $this->assertStringContainsString('إجابة مفلترة.', $onlyPass);
        $this->assertStringNotContainsString('عنوان المزايا', $onlyPass);
    }

    public function test_the_faq_block_renders_exactly_once_and_never_in_the_main_block_pass(): void
    {
        $page = $this->createCompliantServicePage(slug: 'service-faq-single-pass');
        ContentBlock::factory()->for($page)->create(['type' => 'faq', 'data' => ['heading' => 'أسئلة تظهر مرة واحدة فقط']]);
        Faq::factory()->for($page)->create(['question' => 'هل يوجد ضمان؟', 'answer' => 'نلتزم بمراجعة النتيجة معك.']);

        $content = $this->get('/services/service-faq-single-pass')->assertOk()->getContent();

        // The page renders <x-public.blocks> twice (except=faq, then
        // only=faq); the two passes must be disjoint, so neither the FAQ
        // heading nor a question may appear more than once.
        $this->assertSame(1, substr_count($content, 'أسئلة تظهر مرة واحدة فقط'));
        $this->assertSame(1, substr_count($content, 'هل يوجد ضمان؟'));
        $this->assertStringContainsString('نلتزم بمراجعة النتيجة معك.', $content);
    }

    public function test_non_faq_blocks_still_render_in_the_editor_order(): void
    {
        $page = $this->createCompliantServicePage(slug: 'service-block-order');
        ContentBlock::factory()->for($page)->create(['type' => 'features', 'position' => 1, 'data' => ['heading' => 'ماذا تشمل الخدمة', 'items' => [['title' => 'بند أول']]]]);
        ContentBlock::factory()->for($page)->create(['type' => 'steps', 'position' => 2, 'data' => ['heading' => 'خطوات التنفيذ', 'items' => [['title' => 'المعاينة']]]]);
        ContentBlock::factory()->for($page)->create(['type' => 'faq', 'position' => 3, 'data' => ['heading' => 'أسئلة شائعة هنا']]);
        Faq::factory()->for($page)->create(['question' => 'سؤال حقيقي؟', 'answer' => 'إجابة حقيقية.']);

        $content = $this->get('/services/service-block-order')->assertOk()->getContent();

        // Filtering must not reorder what is left in the first pass.
        $this->assertLessThan(
            mb_strpos($content, 'خطوات التنفيذ'),
            mb_strpos($content, 'ماذا تشمل الخدمة'),
            'Non-FAQ blocks must keep the editor order inside the filtered pass.',
        );

        // ...and the FAQ must be lifted out of that flow to sit after the
        // related services and before the closing CTA.
        $this->assertLessThan(
            mb_strpos($content, 'أسئلة شائعة هنا'),
            mb_strpos($content, 'خطوات التنفيذ'),
            'FAQ must render after the other content blocks, not inside them.',
        );
        $this->assertLessThan(
            mb_strpos($content, 'هل تحتاج'),
            mb_strpos($content, 'أسئلة شائعة هنا'),
            'FAQ must render before the final CTA.',
        );
    }

    public function test_faq_renders_after_related_services_and_before_the_final_cta(): void
    {
        $page = $this->createCompliantServicePage(slug: 'service-faq-after-related');
        ContentBlock::factory()->for($page)->create(['type' => 'faq', 'data' => ['heading' => 'أسئلة قبل القرار']]);
        Faq::factory()->for($page)->create(['question' => 'كيف أطلب؟', 'answer' => 'عبر نموذج عرض السعر.']);

        // A sibling service in the same category feeds the related list.
        $this->createCompliantServicePage(slug: 'sibling-service-for-order');
        $sibling = Service::where('id', '!=', $page->pageable->id)->first();

        $content = $this->get('/services/service-faq-after-related')->assertOk()->getContent();

        $this->assertLessThan(
            mb_strpos($content, 'أسئلة قبل القرار'),
            mb_strpos($content, $sibling->name),
            'Related services must come before the FAQ.',
        );
        $this->assertLessThan(
            mb_strpos($content, 'هل تحتاج'),
            mb_strpos($content, 'أسئلة قبل القرار'),
            'FAQ must come before the final CTA.',
        );
    }

    public function test_the_faq_block_only_renders_when_real_faqs_exist(): void
    {
        $page = $this->createCompliantServicePage(slug: 'service-no-faq');
        ContentBlock::factory()->for($page)->create(['type' => 'faq', 'data' => ['heading' => 'أسئلة شائعة عن الخدمة']]);

        $response = $this->get('/services/service-no-faq');

        $response->assertOk();
        $response->assertDontSee('أسئلة شائعة عن الخدمة');
    }

    public function test_the_faq_block_renders_real_attached_questions(): void
    {
        $page = $this->createCompliantServicePage(slug: 'service-with-faq');
        ContentBlock::factory()->for($page)->create(['type' => 'faq', 'data' => ['heading' => 'أسئلة عن هذه الخدمة تحديدًا']]);
        Faq::factory()->for($page)->create(['question' => 'كم تستغرق الخدمة؟', 'answer' => 'عادة أقل من ساعتين.']);

        $response = $this->get('/services/service-with-faq');

        $response->assertSee('أسئلة عن هذه الخدمة تحديدًا');
        $response->assertSee('كم تستغرق الخدمة؟');
    }

    public function test_the_hero_cta_always_points_to_request_quote_never_a_fabricated_price(): void
    {
        $page = $this->createCompliantServicePage(slug: 'service-quote-cta');

        $response = $this->get('/services/service-quote-cta');

        $response->assertSee(route('public.quote').'?service='.$page->pageable->id, false);
        $response->assertDontSee('يبدأ من');
    }

    public function test_a_packages_content_block_renders_real_pricing_only(): void
    {
        $page = $this->createCompliantServicePage(slug: 'service-with-packages', status: PageStatus::Draft);
        ContentBlock::factory()->for($page)->create([
            'type' => 'packages',
            'data' => [
                'heading' => 'باقاتنا',
                'items' => [
                    ['name' => 'باقة أساسية', 'variant' => 'شقة صغيرة', 'price' => 150, 'included_items' => "غرفتين\nصالة"],
                ],
            ],
        ]);
        $page->update(['status' => PageStatus::Published]);

        $response = $this->get('/services/service-with-packages');

        $response->assertOk();
        $response->assertSee('باقة أساسية');
        $response->assertSee('150');
        $response->assertSee('غرفتين');
    }

    public function test_an_active_offer_linked_to_the_service_is_shown(): void
    {
        $page = $this->createCompliantServicePage(slug: 'service-with-active-offer');
        $service = $page->pageable;

        $offer = $this->createPublishedOffer('نظّف الآن واحصل على خصم', slug: 'active-offer', isActive: true, startsAt: now()->subDay(), endsAt: now()->addWeek());
        $offer->services()->attach($service);

        $response = $this->get('/services/service-with-active-offer');

        $response->assertSee('نظّف الآن واحصل على خصم');
    }

    public function test_an_expired_offer_is_never_shown_as_a_current_offer(): void
    {
        $page = $this->createCompliantServicePage(slug: 'service-with-expired-offer');
        $service = $page->pageable;

        $offer = $this->createPublishedOffer('عرض منتهي فعليًا', slug: 'expired-offer', isActive: true, startsAt: now()->subMonth(), endsAt: now()->subWeek());
        $offer->services()->attach($service);

        $response = $this->get('/services/service-with-expired-offer');

        $response->assertDontSee('عرض منتهي فعليًا');
    }

    public function test_an_offer_linked_to_a_different_service_is_not_shown(): void
    {
        $page = $this->createCompliantServicePage(slug: 'service-without-this-offer');
        $otherService = Service::factory()->create();

        $offer = $this->createPublishedOffer('عرض خدمة أخرى', slug: 'other-service-offer', isActive: true, startsAt: now()->subDay(), endsAt: now()->addWeek());
        $offer->services()->attach($otherService);

        $response = $this->get('/services/service-without-this-offer');

        $response->assertDontSee('عرض خدمة أخرى');
    }

    public function test_a_scheduled_offer_is_never_shown_as_a_current_offer(): void
    {
        $page = $this->createCompliantServicePage(slug: 'service-with-scheduled-offer');
        $service = $page->pageable;

        $offer = $this->createPublishedOffer('عرض قادم قريبًا', slug: 'scheduled-offer', isActive: true, startsAt: now()->addWeek(), endsAt: now()->addMonth());
        $offer->services()->attach($service);

        $response = $this->get('/services/service-with-scheduled-offer');

        $response->assertDontSee('عرض قادم قريبًا');
    }

    public function test_an_active_offer_still_shows_even_when_an_expired_offer_sorts_before_it(): void
    {
        $page = $this->createCompliantServicePage(slug: 'service-expired-before-active');
        $service = $page->pageable;

        // The expired offer is given the lower sort_order (and is created
        // first) so it would be attempted first by any naive "take N then
        // filter" approach - the Active offer must still make it through.
        $expired = $this->createPublishedOffer('عرض منتهي يسبق النشط', slug: 'expired-sorts-first', isActive: true, startsAt: now()->subMonth(), endsAt: now()->subWeek());
        $expired->update(['sort_order' => 0]);
        $expired->services()->attach($service);

        $active = $this->createPublishedOffer('عرض نشط يظهر رغم الترتيب', slug: 'active-sorts-second', isActive: true, startsAt: now()->subDay(), endsAt: now()->addWeek());
        $active->update(['sort_order' => 1]);
        $active->services()->attach($service);

        $response = $this->get('/services/service-expired-before-active');

        $response->assertDontSee('عرض منتهي يسبق النشط');
        $response->assertSee('عرض نشط يظهر رغم الترتيب');
    }

    public function test_a_testimonial_linked_to_the_service_is_shown(): void
    {
        $page = $this->createCompliantServicePage(slug: 'service-with-testimonial');
        $service = $page->pageable;

        Testimonial::factory()->create(['service_id' => $service->id, 'author_name' => 'سارة العتيبي', 'content' => 'خدمة ممتازة والتزام بالوقت.']);

        $response = $this->get('/services/service-with-testimonial');

        $response->assertSee('سارة العتيبي');
        $response->assertSee('خدمة ممتازة والتزام بالوقت.');
    }

    public function test_a_testimonial_linked_to_a_different_service_is_not_shown(): void
    {
        $page = $this->createCompliantServicePage(slug: 'service-without-this-testimonial');
        $otherService = Service::factory()->create();

        Testimonial::factory()->create(['service_id' => $otherService->id, 'author_name' => 'عميل خدمة أخرى']);

        $response = $this->get('/services/service-without-this-testimonial');

        $response->assertDontSee('عميل خدمة أخرى');
    }

    public function test_related_services_fallback_shows_automatically_when_no_manual_related_content_block_exists(): void
    {
        $page = $this->createCompliantServicePage(slug: 'service-fallback-related');
        $this->createCompliantServicePage(slug: 'another-real-service');
        $otherService = Service::where('id', '!=', $page->pageable->id)->first();

        $response = $this->get('/services/service-fallback-related');

        $response->assertSee('خدمات ذات صلة');
        $response->assertSee($otherService->name);
    }

    public function test_related_services_fallback_is_suppressed_when_the_editor_already_placed_a_manual_related_content_block(): void
    {
        $page = $this->createCompliantServicePage(slug: 'service-manual-related');
        $this->createCompliantServicePage(slug: 'another-real-service-2');

        ContentBlock::factory()->for($page)->create(['type' => 'related_content', 'data' => []]);

        $response = $this->get('/services/service-manual-related');

        $response->assertDontSee('خدمات ذات صلة');
    }

    public function test_a_project_with_real_before_and_after_photos_renders_the_before_after_showcase(): void
    {
        $page = $this->createCompliantServicePage(slug: 'service-with-before-after');
        $service = $page->pageable;

        $project = $this->createPublishedProject('تنظيف شقة بالكامل', slug: 'project-before-after');
        $before = Media::factory()->create();
        $after = Media::factory()->create();
        $project->media()->attach($before->id, ['stage' => MediaStage::Before->value, 'sort_order' => 0]);
        $project->media()->attach($after->id, ['stage' => MediaStage::After->value, 'sort_order' => 0]);
        $service->projects()->attach($project);

        $response = $this->get('/services/service-with-before-after');

        $response->assertSee('تنظيف شقة بالكامل');
        $response->assertSee('قبل');
        $response->assertSee('بعد');
    }

    public function test_a_project_with_only_an_after_photo_renders_as_a_plain_project_card(): void
    {
        $page = $this->createCompliantServicePage(slug: 'service-with-after-only');
        $service = $page->pageable;

        $project = $this->createPublishedProject('تنظيف مكتب', slug: 'project-after-only');
        $after = Media::factory()->create();
        $project->media()->attach($after->id, ['stage' => MediaStage::After->value, 'sort_order' => 0]);
        $service->projects()->attach($project);

        $response = $this->get('/services/service-with-after-only');

        $response->assertSee('تنظيف مكتب');
        $response->assertDontSee('قبل');
    }

    private function createPublishedOffer(string $title, string $slug, bool $isActive, $startsAt, $endsAt): Offer
    {
        $offer = Offer::factory()->create([
            'title' => $title,
            'is_active' => $isActive,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);

        $offerPage = Page::factory()->create([
            'type' => PageType::Offer,
            'title' => $title,
            'slug' => $slug,
            'status' => PageStatus::Draft,
        ]);
        $offer->page()->save($offerPage);

        ContentBlock::factory()->for($offerPage)->create(['type' => 'rich_text', 'data' => ['content' => 'تفاصيل العرض.']]);

        $offerPage->update(['status' => PageStatus::Published]);

        return $offer;
    }

    private function createPublishedProject(string $title, string $slug): Project
    {
        $project = Project::factory()->create(['title' => $title]);

        $projectPage = Page::factory()->create([
            'type' => PageType::Project,
            'title' => $title,
            'slug' => $slug,
            'status' => PageStatus::Draft,
        ]);
        $project->page()->save($projectPage);

        ContentBlock::factory()->for($projectPage)->create(['type' => 'rich_text', 'data' => ['content' => 'تفاصيل المشروع.']]);

        $projectPage->update(['status' => PageStatus::Published]);

        return $project;
    }
}
