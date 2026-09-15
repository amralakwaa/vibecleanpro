<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Area;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\ContentBlock;
use App\Models\Faq;
use App\Models\Offer;
use App\Models\Page;
use App\Models\Project;
use App\Models\SeoMetadata;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

/**
 * Phase 5 (Design System + Public Frontend Architecture) coverage. This
 * checks that the design system's Blade layer actually renders for every
 * page type without error and stays correctly wired to the SEO Engine -
 * it does not assert on CSS/pixels (see the Phase 5 report's Visual
 * Verification section for the browser-based checks that cover that).
 */
class PublicFrontendTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    public function test_the_homepage_renders_for_an_anonymous_visitor(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_the_homepage_declares_arabic_and_rtl(): void
    {
        $html = $this->get('/')->getContent();

        $this->assertMatchesRegularExpression('#<html[^>]*lang="ar"[^>]*dir="rtl"#', $html);
    }

    public function test_the_homepage_has_no_public_url_for_an_unpublished_area_or_service(): void
    {
        // Regression guard for the Home controller's queries: an Area or
        // Service without a published Page must never appear as a linked
        // card (see HomeController::index()).
        Area::factory()->create(['slug' => 'unpublished-area-home']);
        Service::factory()->create(['name' => 'unpublished-service-home']);

        $html = $this->get('/')->getContent();

        $this->assertStringNotContainsString('unpublished-area-home', $html);
        $this->assertStringNotContainsString('unpublished-service-home', $html);
    }

    public function test_a_service_page_renders_with_seo_head_data_integrated(): void
    {
        $this->createCompliantServicePage(slug: 'frontend-service');

        $response = $this->get('/services/frontend-service');

        $response->assertOk();
        $html = $response->getContent();
        $this->assertMatchesRegularExpression('#<html[^>]*lang="ar"[^>]*dir="rtl"#', $html);
        $this->assertStringContainsString('<link rel="canonical"', $html);
        $this->assertStringContainsString('<meta name="robots" content="index, follow">', $html);
        $this->assertStringContainsString('application/ld+json', $html);
    }

    public function test_an_area_page_renders_without_error_and_is_visually_distinct_from_a_service_page(): void
    {
        $this->createCompliantAreaPage(slug: 'frontend-area');

        $response = $this->get('/areas/frontend-area');

        $response->assertOk();
        // The Area template has no <x-public.hero> image band (see
        // pages/area.blade.php) - a Service page always does.
        $this->assertStringNotContainsString('object-cover opacity-25', $response->getContent());
    }

    public function test_a_project_page_renders_without_error(): void
    {
        $area = Area::factory()->create();
        $project = Project::factory()->create(['area_id' => $area->id, 'summary' => 'ملخص تجريبي.']);
        $page = Page::factory()->create(['type' => PageType::Project, 'slug' => 'frontend-project']);
        $project->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'نص.']]);
        SeoMetadata::factory()->for($page)->create();
        $page->update(['status' => PageStatus::Published]);

        $this->get('/projects/frontend-project')->assertOk();
    }

    public function test_an_article_page_renders_without_error(): void
    {
        $category = ArticleCategory::factory()->create();
        $article = Article::factory()->create(['article_category_id' => $category->id]);
        $page = Page::factory()->create(['type' => PageType::Article, 'slug' => 'frontend-article']);
        $article->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'محتوى.']]);
        SeoMetadata::factory()->for($page)->create();
        $page->update(['status' => PageStatus::Published]);

        $this->get('/blog/frontend-article')->assertOk();
    }

    public function test_an_offer_page_renders_without_error_including_an_expired_offer(): void
    {
        $offer = Offer::factory()->create(['ends_at' => now()->subWeek()]);
        $page = Page::factory()->create(['type' => PageType::Offer, 'slug' => 'frontend-offer-expired']);
        $offer->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'نص.']]);
        SeoMetadata::factory()->for($page)->create();
        $page->update(['status' => PageStatus::Published]);

        $this->get('/offers/frontend-offer-expired')->assertOk();
    }

    public function test_a_standalone_legal_page_renders_without_error(): void
    {
        $page = Page::factory()->create(['type' => PageType::Legal, 'slug' => 'frontend-legal', 'title' => 'الشروط والأحكام']);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'نص الشروط.']]);
        $page->update(['status' => PageStatus::Published]);

        $this->get('/frontend-legal')->assertOk();
    }

    public function test_content_block_types_render_without_error_features_faq_and_related_content(): void
    {
        $page = $this->createCompliantServicePage(slug: 'blocks-sweep', status: PageStatus::Draft);
        ContentBlock::factory()->for($page)->create(['type' => 'features', 'data' => ['heading' => 'مزايا', 'items' => [['title' => 'ميزة', 'description' => 'وصف']]]]);
        ContentBlock::factory()->for($page)->create(['type' => 'steps', 'data' => ['heading' => 'خطوات', 'items' => [['title' => 'خطوة', 'description' => 'وصف']]]]);
        ContentBlock::factory()->for($page)->create(['type' => 'faq', 'data' => ['heading' => 'أسئلة']]);
        Faq::factory()->for($page)->create(['question' => 'سؤال؟', 'answer' => 'إجابة.']);
        $page->update(['status' => PageStatus::Published]);

        $response = $this->get('/services/blocks-sweep');

        $response->assertOk();
        $response->assertSee('مزايا');
        $response->assertSee('خطوات');
        $response->assertSee('سؤال؟');
    }

    public function test_admin_routes_remain_protected_while_public_routes_do_not_require_authentication(): void
    {
        $this->createCompliantServicePage(slug: 'no-auth-needed');

        $this->get('/services/no-auth-needed')->assertOk();
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_the_header_navigation_points_at_real_indexable_routes_not_homepage_anchors(): void
    {
        $html = $this->get('/')->getContent();

        // Phase 6 replaced the homepage-anchor placeholders with real
        // listing routes (see the Phase 6 report, item 3) - the nav must
        // never fall back to "#services"-style anchors again.
        $this->assertStringNotContainsString('#services', $html);
        $this->assertStringContainsString('href="'.route('public.services.index').'"', $html);
        $this->assertStringContainsString('href="'.route('public.areas.index').'"', $html);
        $this->assertStringContainsString('href="'.route('public.contact').'"', $html);
    }

    public function test_the_mobile_cta_bar_and_header_cta_are_absent_without_contact_details(): void
    {
        // No BusinessProfile row at all: nothing to fabricate a WhatsApp/
        // phone link from, so neither must appear (see the Phase 5
        // report's Trust System guardrail against invented data).
        $this->createCompliantServicePage(slug: 'no-contact-details');

        $html = $this->get('/services/no-contact-details')->getContent();

        $this->assertStringNotContainsString('wa.me', $html);
        $this->assertStringNotContainsString('href="tel:', $html);
    }

    public function test_the_service_package_card_component_renders_a_real_discount_without_inventing_one(): void
    {
        $withDiscount = Blade::render(
            '<x-public.service-package-card name="تنظيف شقة" variant="غرفتين" :price="675" :previous-price="879" :included-items="[\'عنصر أول\']" />'
        );
        $noDiscount = Blade::render(
            '<x-public.service-package-card name="تنظيف شقة" :price="675" />'
        );

        $this->assertStringContainsString('تنظيف شقة', $withDiscount);
        $this->assertMatchesRegularExpression('/<s[^>]*>879 ر\.س<\/s>/u', $withDiscount);
        // The previous price is shown as a fact; no percentage is computed from it.
        $this->assertStringNotContainsString('%', $withDiscount);
        // No previousPrice passed at all: never fabricate a "before" price.
        $this->assertDoesNotMatchRegularExpression('/<s[\s>]/', $noDiscount);

        // A "previous" price that is not higher than the current one is not a discount.
        $bogus = Blade::render('<x-public.service-package-card name="تنظيف شقة" :price="675" :previous-price="600" />');
        $this->assertDoesNotMatchRegularExpression('/<s[\s>]/', $bogus);
    }

    public function test_the_trust_card_and_certification_card_components_render_without_error(): void
    {
        $trust = Blade::render('<x-public.trust-card icon="shield-check" title="ضمان حقيقي" description="وصف حقيقي." />');
        $certification = Blade::render(
            '<x-public.certification-card name="اعتماد الدفاع المدني" issuer="المديرية العامة للدفاع المدني" certificate-number="DC-1234" />'
        );

        $this->assertStringContainsString('ضمان حقيقي', $trust);
        $this->assertStringContainsString('اعتماد الدفاع المدني', $certification);
        $this->assertStringContainsString('DC-1234', $certification);
    }
}
