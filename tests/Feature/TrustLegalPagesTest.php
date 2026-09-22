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

class TrustLegalPagesTest extends TestCase
{
    use RefreshDatabase;

    private function publishPage(string $slug, PageType $type, string $title, string $body = '<p>محتوى.</p>'): Page
    {
        $page = Page::factory()->create(['type' => $type, 'slug' => $slug, 'title' => $title]);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => $body]]);
        SeoMetadata::factory()->for($page)->create(['robots_index' => true, 'robots_follow' => true]);
        $page->update(['status' => PageStatus::Published, 'published_at' => now()]);

        return $page;
    }

    public function test_trust_hub_returns_200(): void
    {
        $this->publishPage('trust', PageType::Trust, 'مركز الثقة وحقوق العملاء');

        $this->get('/trust')->assertStatus(200);
    }

    public function test_warranty_page_returns_200(): void
    {
        $this->publishPage('warranty', PageType::Trust, 'ضمان الخدمة');

        $this->get('/warranty')->assertStatus(200);
    }

    public function test_privacy_page_returns_200(): void
    {
        $this->publishPage('privacy', PageType::Legal, 'سياسة الخصوصية');

        $this->get('/privacy')->assertStatus(200);
    }

    public function test_terms_page_returns_200(): void
    {
        $this->publishPage('terms', PageType::Legal, 'الشروط والأحكام');

        $this->get('/terms')->assertStatus(200);
    }

    public function test_complaints_page_returns_200(): void
    {
        $this->publishPage('complaints', PageType::Legal, 'الشكاوى والتعويضات');

        $this->get('/complaints')->assertStatus(200);
    }

    public function test_cancellation_page_returns_200(): void
    {
        $this->publishPage('cancellation', PageType::Legal, 'الإلغاء والمدفوعات');

        $this->get('/cancellation')->assertStatus(200);
    }

    public function test_service_scope_page_returns_200(): void
    {
        $this->publishPage('service-scope', PageType::Legal, 'نطاق الخدمة وحماية الممتلكات');

        $this->get('/service-scope')->assertStatus(200);
    }

    public function test_licenses_compliance_page_returns_200(): void
    {
        $this->publishPage('licenses-compliance', PageType::Legal, 'الامتثال والتراخيص');

        $this->get('/licenses-compliance')->assertStatus(200);
    }

    public function test_trust_page_renders_body_content(): void
    {
        $this->publishPage('trust', PageType::Trust, 'مركز الثقة', '<p>مركز الثقة وحقوق العملاء الفعلية.</p>');

        $this->get('/trust')->assertSee('مركز الثقة وحقوق العملاء الفعلية.');
    }

    public function test_legal_page_renders_body_content(): void
    {
        $this->publishPage('terms', PageType::Legal, 'الشروط والأحكام', '<p>هذه شروط الاستخدام.</p>');

        $this->get('/terms')->assertSee('هذه شروط الاستخدام.');
    }

    public function test_trust_page_has_canonical_link(): void
    {
        $this->publishPage('warranty', PageType::Trust, 'ضمان الخدمة');

        $this->get('/warranty')->assertSee('rel="canonical"', false);
    }

    public function test_trust_page_is_not_noindex(): void
    {
        $this->publishPage('complaints', PageType::Legal, 'الشكاوى');

        $content = $this->get('/complaints')->getContent();

        $this->assertStringNotContainsString('noindex', $content);
    }

    public function test_warranty_page_has_no_fake_ten_year_claim(): void
    {
        $this->publishPage('warranty', PageType::Trust, 'ضمان الخدمة', '<p>الضمان يغطي جودة التنفيذ المتفق عليه.</p>');

        $content = $this->get('/warranty')->getContent();

        $this->assertStringNotContainsString('10 سنوات', $content);
        $this->assertStringNotContainsString('عشر سنوات', $content);
        $this->assertStringContainsString('الضمان', $content);
    }

    public function test_privacy_page_can_render_pdpl_content(): void
    {
        $this->publishPage('privacy', PageType::Legal, 'سياسة الخصوصية',
            '<p>نلتزم بنظام حماية البيانات الشخصية (PDPL).</p>');

        $content = $this->get('/privacy')->getContent();

        $this->assertTrue(
            str_contains($content, 'PDPL') || str_contains($content, 'نظام حماية البيانات'),
            'Privacy page should render PDPL body text'
        );
    }

    public function test_licenses_page_does_not_claim_fake_pest_licence(): void
    {
        $this->publishPage('licenses-compliance', PageType::Legal, 'الامتثال',
            '<p>نلتزم بمتطلبات الجهات المختصة.</p>');

        $content = $this->get('/licenses-compliance')->getContent();

        $this->assertStringNotContainsString('مرخصة من وزارة البيئة', $content);
    }

    public function test_trust_column_appears_in_footer(): void
    {
        $this->publishPage('trust', PageType::Trust, 'مركز الثقة');

        $content = $this->get('/trust')->getContent();

        $this->assertStringContainsString('الثقة والسياسات', $content);
    }

    public function test_cta_block_renders_whatsapp_url(): void
    {
        $page = Page::factory()->create(['type' => PageType::Trust, 'slug' => 'warranty', 'title' => 'ضمان الخدمة']);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => '<p>سياسة الضمان.</p>']]);
        ContentBlock::factory()->for($page)->create(['type' => 'cta', 'data' => [
            'heading' => 'لديك ملاحظة؟', 'button_label' => 'تواصل', 'button_url' => 'https://wa.me/966534999194',
        ]]);
        SeoMetadata::factory()->for($page)->create();
        $page->update(['status' => PageStatus::Published, 'published_at' => now()]);

        $this->get('/warranty')
            ->assertStatus(200)
            ->assertSee('wa.me/966534999194', false);
    }

    public function test_faq_block_renders_faqs(): void
    {
        $page = Page::factory()->create(['type' => PageType::Legal, 'slug' => 'complaints', 'title' => 'الشكاوى']);
        ContentBlock::factory()->for($page)->create(['type' => 'faq', 'data' => ['heading' => 'الأسئلة', 'source' => 'page']]);
        Faq::factory()->for($page)->create(['question' => 'كيف أقدم شكواي؟', 'answer' => 'عبر واتساب.']);
        SeoMetadata::factory()->for($page)->create();
        $page->update(['status' => PageStatus::Published, 'published_at' => now()]);

        $this->get('/complaints')
            ->assertStatus(200)
            ->assertSee('كيف أقدم شكواي؟');
    }

    public function test_pages_have_no_placeholder_text(): void
    {
        $this->publishPage('service-scope', PageType::Legal, 'نطاق الخدمة', '<p>وصف نطاق الخدمة الحقيقي.</p>');

        $content = $this->get('/service-scope')->getContent();

        $this->assertStringNotContainsString('TODO', $content);
        $this->assertStringNotContainsString('PLACEHOLDER', $content);
    }
}
