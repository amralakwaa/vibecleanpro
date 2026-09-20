<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\BusinessProfile;
use App\Models\Page;
use App\Seo\PublishingGate;
use Database\Seeders\CompanyProfileSeeder;
use Database\Seeders\TrustPagesDraftSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarrantyAndTrustTest extends TestCase
{
    use RefreshDatabase;

    private function profile(): BusinessProfile
    {
        $this->seed(CompanyProfileSeeder::class);

        return BusinessProfile::query()->sole();
    }

    private function warrantyPage(PageStatus $status = PageStatus::Draft): Page
    {
        $page = Page::factory()->create(['type' => PageType::Trust, 'title' => 'الضمان وشروط الخدمة', 'slug' => 'warranty', 'status' => PageStatus::Draft]);
        $this->seed(TrustPagesDraftSeeder::class);

        if ($status === PageStatus::Published) {
            $page->refresh()->update(['status' => PageStatus::Published, 'published_at' => now()]);
        }

        return $page->fresh();
    }

    public function test_the_warranty_page_states_the_commitment_and_its_conditions(): void
    {
        $this->profile();
        $this->warrantyPage(PageStatus::Published);

        $html = $this->get('/warranty')->assertOk()->getContent();

        $this->assertStringContainsString('يصل إلى 10 سنوات', $html);
        $this->assertStringContainsString('شروط الاستفادة من الضمان', $html);
        $this->assertStringContainsString('كيف تطلب مراجعة الضمان', $html);
        $this->assertStringContainsString('info@vibecleanpro.com', $html);
        $this->assertSame(1, substr_count($html, '<h1'));
    }

    public function test_the_warranty_page_publishes_without_any_pending_owner_decision(): void
    {
        $this->profile();
        $page = $this->warrantyPage();

        $this->assertSame([], app(PublishingGate::class)->evaluate($page)->errors()->pluck('key')->all());
        $this->assertStringNotContainsString(PublishingGate::OWNER_INPUT_MARKER, json_encode($page->contentBlocks->pluck('data'), JSON_UNESCAPED_UNICODE));
    }

    public function test_the_warranty_page_invents_no_certificate_period_or_legal_term(): void
    {
        $this->profile();
        $this->warrantyPage(PageStatus::Published);
        $html = $this->get('/warranty')->getContent();

        // No invented certificate, issuer, registration number, per-service
        // period or legal jurisdiction - the owner supplies those.
        foreach (['ISO', 'الهيئة السعودية', 'شهادة رقم', 'المحكمة', 'وفقًا للمادة', 'سنتان', '5 سنوات على'] as $invented) {
            $this->assertStringNotContainsString($invented, $html, "warranty page must not invent: {$invented}");
        }
    }

    public function test_the_trust_strip_comes_from_the_business_profile_and_appears_where_it_matters(): void
    {
        $this->profile();

        foreach (['/', '/contact'] as $path) {
            $this->get($path)->assertOk();
        }

        $home = $this->get('/')->getContent();
        $this->assertStringContainsString('فريق سعودي مدرب', $home);
        $this->assertStringContainsString('ضمان يصل إلى 10 سنوات', $home);

        // Editing the profile changes every surface at once. The negative
        // assertion uses the point's description, not its title: the words
        // "فريق سعودي مدرب" also appear in the profile's identity
        // statement, which is a different field on the same page.
        BusinessProfile::query()->update(['trust_points' => [['title' => 'نقطة محرّرة', 'description' => 'وصف']]]);
        $home = $this->get('/')->getContent();
        $this->assertStringContainsString('نقطة محرّرة', $home);
        $this->assertStringNotContainsString('الفريق الذي يصل إليك هو فريقنا', $home);
    }

    public function test_the_trust_strip_disappears_entirely_when_no_points_are_set(): void
    {
        $this->profile();
        BusinessProfile::query()->update(['trust_points' => null]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('التزاماتنا', $html);
        $this->assertStringNotContainsString('ضمان يصل إلى', $html);
    }

    public function test_the_warranty_link_appears_only_once_the_page_is_published(): void
    {
        $this->profile();
        $this->warrantyPage();

        $draftHtml = $this->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString(url('/warranty'), $draftHtml);

        Page::query()->where('slug', 'warranty')->update(['status' => PageStatus::Published, 'published_at' => now()]);

        $publishedHtml = $this->get('/')->getContent();
        $this->assertStringContainsString(url('/warranty'), $publishedHtml);
        $this->assertStringContainsString('تفاصيل الضمان وشروط الخدمة', $publishedHtml);
    }
}
