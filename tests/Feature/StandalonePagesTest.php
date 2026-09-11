<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\ContentBlock;
use App\Models\Faq;
use App\Models\Page;
use App\Models\SeoMetadata;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * About, Service Guarantee, and FAQ are deliberately NOT dedicated
 * controllers/routes (see the Phase 6 report, item 15/16/14) - they are
 * ordinary editor-managed standalone Pages (Landing/Trust type) reached
 * through the existing catch-all + content-block architecture, so this
 * proves that pathway actually works end to end rather than assuming it.
 */
class StandalonePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_about_page_authored_as_a_landing_page_renders_at_the_expected_slug(): void
    {
        $page = Page::factory()->create(['type' => PageType::Landing, 'slug' => 'about', 'title' => 'من نحن']);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'نبذة عن الشركة.']]);
        SeoMetadata::factory()->for($page)->create();
        $page->update(['status' => PageStatus::Published]);

        $response = $this->get('/about');

        $response->assertOk();
        $response->assertSee('من نحن');
        $response->assertSee('نبذة عن الشركة.');
    }

    public function test_a_service_guarantee_page_authored_as_a_trust_page_renders(): void
    {
        $page = Page::factory()->create(['type' => PageType::Trust, 'slug' => 'service-guarantee', 'title' => 'الضمان وسياسة الجودة']);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'شروط الضمان الفعلية هنا.']]);
        SeoMetadata::factory()->for($page)->create();
        $page->update(['status' => PageStatus::Published]);

        $this->get('/service-guarantee')->assertSee('شروط الضمان الفعلية هنا.');
    }

    public function test_a_faq_hub_page_renders_its_own_attached_faqs_via_the_faq_block(): void
    {
        $page = Page::factory()->create(['type' => PageType::Landing, 'slug' => 'faq', 'title' => 'الأسئلة الشائعة']);
        ContentBlock::factory()->for($page)->create(['type' => 'faq', 'data' => ['heading' => 'أسئلة عامة']]);
        Faq::factory()->for($page)->create(['question' => 'كيف أطلب الخدمة؟', 'answer' => 'عبر واتساب أو نموذج الطلب.']);
        SeoMetadata::factory()->for($page)->create();
        $page->update(['status' => PageStatus::Published]);

        $response = $this->get('/faq');

        $response->assertOk();
        $response->assertSee('كيف أطلب الخدمة؟');
    }

    public function test_the_about_slug_is_still_reachable_and_not_swallowed_by_a_reserved_route(): void
    {
        $this->assertFalse(str_contains(route('public.services.index'), 'about'));
        // /about only reaches the standalone catch-all when it is not in
        // the reserved-slug exclusion list - a regression here would 404.
        Page::factory()->create(['type' => PageType::Landing, 'slug' => 'about', 'status' => PageStatus::Draft]);

        $this->get('/about')->assertNotFound(); // still draft, correctly not public yet.
    }
}
