<?php

namespace Tests\Feature\Seo;

use App\Models\User;
use App\Seo\PageQualityScorer;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

class PageQualityScorerTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    public function test_a_fully_compliant_service_page_scores_100_on_every_applicable_dimension(): void
    {
        $page = $this->createCompliantServicePage();

        $breakdown = app(PageQualityScorer::class)->score($page);

        $this->assertSame(100, $breakdown->overall);
        $this->assertSame(100, $breakdown->technical);
        $this->assertSame(100, $breakdown->content);
        $this->assertSame(100, $breakdown->trust);
        $this->assertSame(100, $breakdown->internalLinking);
    }

    public function test_local_dimension_is_not_applicable_for_a_service_page(): void
    {
        $page = $this->createCompliantServicePage();

        $breakdown = app(PageQualityScorer::class)->score($page);

        $this->assertNull($breakdown->local);
    }

    public function test_local_dimension_is_applicable_and_scored_for_an_area_page(): void
    {
        $page = $this->createCompliantAreaPage();

        $breakdown = app(PageQualityScorer::class)->score($page);

        $this->assertSame(100, $breakdown->local);
    }

    public function test_a_missing_seo_title_and_description_lowers_only_the_content_dimension(): void
    {
        $page = $this->createCompliantServicePage();
        $page->seoMetadata->update(['meta_title' => null, 'meta_description' => null]);

        $breakdown = app(PageQualityScorer::class)->score($page->fresh(['contentBlocks', 'seoMetadata', 'pageable']));

        // 3 of 5 content checks still pass (featured_image, image_alt; the
        // service has no author check) - only seo_title/meta_description fail.
        $this->assertLessThan(100, $breakdown->content);
        $this->assertSame(100, $breakdown->technical, 'A content gap must not drag down the technical dimension.');
    }

    public function test_zero_inbound_links_lowers_only_the_internal_linking_dimension(): void
    {
        $page = $this->createCompliantServicePage();
        $page->pageable->areas()->detach();
        $page->pageable->projects()->detach();

        $breakdown = app(PageQualityScorer::class)->score($page->fresh(['contentBlocks', 'seoMetadata', 'pageable']));

        $this->assertSame(0, $breakdown->internalLinking);
        $this->assertSame(100, $breakdown->technical);
        $this->assertSame(100, $breakdown->content);
    }

    public function test_the_overall_score_is_the_average_of_only_the_applicable_dimensions(): void
    {
        $page = $this->createCompliantServicePage();
        $page->pageable->areas()->detach();
        $page->pageable->projects()->detach();

        $breakdown = app(PageQualityScorer::class)->score($page->fresh(['contentBlocks', 'seoMetadata', 'pageable']));

        // technical(100) + content(100) + trust(0, since related_content and
        // cta both live under "trust" and related_content now fails too) +
        // internalLinking(0), averaged over the 4 applicable dimensions
        // (local is null for a Service page and must not count).
        $expected = (int) round((100 + 100 + $breakdown->trust + 0) / 4);
        $this->assertSame($expected, $breakdown->overall);
    }

    public function test_the_score_is_never_labeled_as_a_google_score_anywhere_it_is_rendered(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('Super Admin', 'web'));
        $this->actingAs($admin);

        $page = $this->createCompliantServicePage();

        $response = $this->get("/admin/pages/{$page->id}/edit");

        $response->assertOk();
        $response->assertDontSee('Google Score', false);
        $response->assertDontSee('Google Ranking Score', false);
        $response->assertDontSee('درجة جوجل', false);
        $response->assertDontSee('تصنيف جوجل', false);
        $response->assertSee('نقاط جودة الصفحة (داخلي)', false);
        $response->assertSee('معاينة تقريبية', false);
    }
}
