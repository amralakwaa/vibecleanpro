<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Area;
use App\Models\ContentBlock;
use App\Models\Faq;
use App\Models\Page;
use App\Models\SeoMetadata;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
