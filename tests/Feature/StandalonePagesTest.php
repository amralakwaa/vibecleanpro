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

    public function test_the_faq_hub_renders_the_sitewide_pool_only_when_its_block_says_so(): void
    {
        Faq::factory()->create(['page_id' => null, 'question' => 'سؤال-عام-من-المخزون؟', 'answer' => 'إجابة.', 'sort_order' => 2]);
        Faq::factory()->create(['page_id' => null, 'question' => 'سؤال-عام-أول؟', 'answer' => 'إجابة.', 'sort_order' => 1]);
        Faq::factory()->create(['page_id' => null, 'question' => 'سؤال-عام-معطل؟', 'answer' => 'إجابة.', 'is_active' => false]);

        $hub = $this->publishedStandalone('faq', PageType::Trust, ['type' => 'faq', 'data' => ['heading' => 'أسئلة عامة', 'source' => 'sitewide']]);

        $html = $this->get('/faq')->assertOk()->getContent();

        $this->assertStringContainsString('سؤال-عام-من-المخزون؟', $html);
        $this->assertStringNotContainsString('سؤال-عام-معطل؟', $html);
        $this->assertLessThan(mb_strpos($html, 'سؤال-عام-من-المخزون؟'), mb_strpos($html, 'سؤال-عام-أول؟'));
        $this->assertSame(1, substr_count($this->stripScripts($html), 'سؤال-عام-أول؟'));

        // A page-specific FAQ on the hub itself does not get merged in:
        // the block's source decides, one set at a time.
        Faq::factory()->for($hub)->create(['question' => 'سؤال-خاص-بالمركز؟', 'answer' => 'إجابة.']);
        $again = $this->get('/faq')->getContent();
        $this->assertStringContainsString('سؤال-عام-من-المخزون؟', $again);
        $this->assertStringNotContainsString('سؤال-خاص-بالمركز؟', $this->stripScripts($again));
    }

    public function test_a_faq_block_left_on_page_source_never_pulls_the_pool_in_even_with_no_page_faqs(): void
    {
        Faq::factory()->create(['page_id' => null, 'question' => 'سؤال-عام-لا-يتسرب؟', 'answer' => 'إجابة.']);

        // Default source (omitted, as older blocks are stored) and an
        // explicit "page": both stay silent without their own FAQs.
        $this->publishedStandalone('service-guarantee', PageType::Trust, ['type' => 'faq', 'data' => ['heading' => 'أسئلة الضمان']]);
        $this->publishedStandalone('terms', PageType::Legal, ['type' => 'faq', 'data' => ['heading' => 'أسئلة الشروط', 'source' => 'page']]);

        foreach (['/service-guarantee', '/terms'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertStringNotContainsString('سؤال-عام-لا-يتسرب؟', $html);
        }
    }

    public function test_a_standalone_page_with_its_own_faqs_renders_only_those(): void
    {
        Faq::factory()->create(['page_id' => null, 'question' => 'سؤال-عام-لا-يظهر؟', 'answer' => 'إجابة.']);
        $page = $this->publishedStandalone('service-guarantee', PageType::Trust, ['type' => 'faq', 'data' => ['heading' => 'أسئلة الضمان']]);
        Faq::factory()->for($page)->create(['question' => 'سؤال-خاص-بالضمان؟', 'answer' => 'إجابة.']);

        $html = $this->get('/service-guarantee')->assertOk()->getContent();

        $body = $this->stripScripts($html);
        $this->assertSame(1, substr_count($body, 'سؤال-خاص-بالضمان؟'));
        $this->assertStringNotContainsString('سؤال-عام-لا-يظهر؟', $body);
    }

    public function test_a_standalone_page_without_a_faq_block_never_pulls_the_pool_in(): void
    {
        $page = Page::factory()->create(['type' => PageType::Legal, 'slug' => 'privacy', 'title' => 'الخصوصية']);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => '<p>نص السياسة.</p>']]);
        SeoMetadata::factory()->for($page)->create();
        $page->update(['status' => PageStatus::Published]);
        Faq::factory()->create(['page_id' => null, 'question' => 'سؤال-لا-ينتمي-هنا؟', 'answer' => 'إجابة.']);

        $html = $this->get('/privacy')->assertOk()->getContent();

        $this->assertStringContainsString('نص السياسة.', $html);
        $this->assertStringNotContainsString('سؤال-لا-ينتمي-هنا؟', $html);
        $this->assertSame(1, substr_count($html, '<h1'));
    }

    private function publishedStandalone(string $slug, PageType $type, array $block): Page
    {
        $page = Page::factory()->create(['type' => $type, 'slug' => $slug, 'title' => 'صفحة '.$slug]);
        ContentBlock::factory()->for($page)->create($block);
        SeoMetadata::factory()->for($page)->create();
        $page->update(['status' => PageStatus::Published]);

        return $page;
    }
}
