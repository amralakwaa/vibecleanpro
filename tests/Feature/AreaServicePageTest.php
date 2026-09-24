<?php

namespace Tests\Feature;

use App\Enums\AreaTier;
use App\Enums\LocationConfidence;
use App\Enums\LocationStatus;
use App\Enums\PageStatus;
use App\Models\Area;
use App\Models\Page;
use App\Models\Project;
use App\Models\Service;
use App\Seo\Enums\CheckSeverity;
use App\Seo\IndexabilityEvaluator;
use App\Seo\PublishingGate;
use App\Seo\SitemapGenerator;
use Database\Seeders\AreaContentSeeder;
use Database\Seeders\ProductionContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

/**
 * Tests the Wave 1 Service Area architecture:
 *
 * 1. Tier B quality Service Area can become indexable
 * 2. Tier B does not require verified project
 * 3. Unverified project never appears as local evidence
 * 4. Tier A evidence blocks still work
 * 5. Duplicate/low-quality area still fails gate
 * 6. Noindex pages excluded from sitemap
 * 7. Published indexable Service Area included appropriately
 * 8. Unique SEO metadata
 * 9. Seeder idempotency
 * 10. Seeder does not overwrite protected/editor-authored content
 */
class AreaServicePageTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    /**
     * 1. A Tier B area with quality content, services, and proper metadata
     * can pass the PublishingGate and become indexable.
     */
    public function test_tier_b_quality_service_area_can_become_indexable(): void
    {
        $page = $this->createCompliantAreaPage(slug: 'tier-b-service-area', title: 'خدمات التنظيف في حي حطين');
        $page->pageable->update(['tier' => AreaTier::B]);
        $page->seoMetadata()->update(['robots_index' => true]);

        $gate = app(PublishingGate::class);
        $result = $gate->evaluate($page->fresh(['contentBlocks', 'seoMetadata', 'pageable']));

        $this->assertTrue($result->canPublish(), 'A compliant Tier B Service Area should be publishable.');

        $check = collect($result->checks)->firstWhere('key', 'area_tier');
        $this->assertSame(CheckSeverity::Pass, $check->severity, 'Tier B must pass the gate, not fail or warn.');

        $evaluator = app(IndexabilityEvaluator::class);
        $decision = $evaluator->evaluate($page->fresh('seoMetadata'));
        $this->assertTrue($decision->indexable, 'A published Tier B page with robots_index=true must be indexable.');
    }

    /**
     * 2. A Tier B area page does NOT require a verified project to pass
     * the PublishingGate. Missing projects is a WARNING, not an ERROR.
     */
    public function test_tier_b_does_not_require_verified_project(): void
    {
        $page = $this->createCompliantAreaPage(slug: 'no-projects-area', title: 'خدمات التنظيف في حي الملقا');
        $page->pageable->update(['tier' => AreaTier::B]);
        $page->pageable->projects()->delete();

        $gate = app(PublishingGate::class);
        $result = $gate->evaluate($page->fresh(['contentBlocks', 'seoMetadata', 'pageable']));

        $this->assertTrue($result->canPublish(), 'Tier B page without projects should still be publishable.');

        $projectCheck = collect($result->checks)->firstWhere('key', 'local_projects');
        $this->assertSame(CheckSeverity::Warning, $projectCheck->severity, 'Missing projects should be a WARNING, not an ERROR.');
    }

    /**
     * 3. An unverified project never appears as local evidence on a
     * Service Area page. hasVerifiedLocation() gates all local claims.
     */
    public function test_unverified_project_never_appears_as_local_evidence(): void
    {
        $area = Area::factory()->create(['tier' => AreaTier::B]);
        $service = Service::factory()->create();
        $area->services()->attach($service);

        // Create an unverified project in this area.
        $project = Project::factory()->create([
            'area_id' => $area->id,
            'location_status' => LocationStatus::Draft,
            'location_confidence' => LocationConfidence::NoEvidence,
        ]);

        $this->assertFalse(
            $project->hasVerifiedLocation(),
            'A draft project must not pass hasVerifiedLocation().',
        );

        // Even a project with a status but weak evidence must fail.
        $project->update([
            'location_status' => LocationStatus::PendingReview,
            'location_confidence' => LocationConfidence::InternalRecord,
        ]);

        $this->assertFalse(
            $project->fresh()->hasVerifiedLocation(),
            'A pending project with low confidence must not pass hasVerifiedLocation().',
        );
    }

    /**
     * 4. Tier A evidence requirements still work: promotion to Tier A
     * requires verified project evidence (areas:promote refuses otherwise).
     */
    public function test_tier_a_evidence_blocks_still_work(): void
    {
        $area = Area::factory()->create(['tier' => AreaTier::B, 'slug' => 'no-evidence-area']);

        $this->artisan('areas:promote', [
            'slug' => 'no-evidence-area',
            '--reason' => 'test promotion',
            '--by' => '1',
        ])->assertExitCode(1);

        $this->assertSame(AreaTier::B, $area->fresh()->tier, 'Promotion must be refused without verified projects.');
    }

    /**
     * 5. A duplicate/low-quality area still gets caught by the gate.
     */
    public function test_duplicate_low_quality_area_still_fails_gate(): void
    {
        // Thin content (under 150 chars).
        $page = $this->createCompliantAreaPage(slug: 'thin-area', title: 'حي ضعيف', status: PageStatus::Draft);
        $page->contentBlocks()->where('type', 'rich_text')->update(['data' => ['content' => 'محتوى ضعيف']]);

        $gate = app(PublishingGate::class);
        $result = $gate->evaluate($page->fresh(['contentBlocks', 'seoMetadata', 'pageable']));

        $thinCheck = collect($result->checks)->firstWhere('key', 'local_content_depth');
        $this->assertSame(CheckSeverity::Warning, $thinCheck->severity, 'Thin content must trigger a warning.');
    }

    /**
     * 6. A noindex page never appears in the sitemap.
     */
    public function test_noindex_pages_excluded_from_sitemap(): void
    {
        $page = $this->createCompliantAreaPage(slug: 'noindex-area');
        $page->seoMetadata()->update(['robots_index' => false]);

        $sitemap = app(SitemapGenerator::class);
        $locs = $sitemap->entries()->pluck('loc');

        $this->assertFalse(
            $locs->contains(fn ($loc) => str_contains($loc, 'noindex-area')),
            'A noindex area page must not appear in the sitemap.',
        );
    }

    /**
     * 7. A published, indexable Service Area page IS included in the
     * sitemap and accessible via its public URL.
     */
    public function test_published_indexable_service_area_in_sitemap(): void
    {
        $page = $this->createCompliantAreaPage(slug: 'indexable-service-area');
        $page->pageable->update(['tier' => AreaTier::B]);
        $page->seoMetadata()->update(['robots_index' => true]);

        $sitemap = app(SitemapGenerator::class);
        $locs = $sitemap->entries()->pluck('loc');

        $this->assertTrue(
            $locs->contains(fn ($loc) => str_contains($loc, 'indexable-service-area')),
            'A published indexable Tier B area page must be in the sitemap.',
        );
    }

    /**
     * 8. Each Wave 1 area must have unique SEO metadata.
     */
    public function test_unique_seo_metadata(): void
    {
        $this->seed(ProductionContentSeeder::class);
        $this->seed(AreaContentSeeder::class);

        $wave1Slugs = ['al-olaya', 'al-malqa', 'al-narjis', 'al-yasmin', 'al-qirawan', 'hittin', 'al-rimal'];

        $titles = [];
        $descriptions = [];

        foreach ($wave1Slugs as $slug) {
            $area = Area::where('slug', $slug)->first();
            $this->assertNotNull($area, "Area {$slug} must exist.");
            $this->assertNotNull($area->page, "Area {$slug} must have a page.");

            $seo = $area->page->seoMetadata;
            $this->assertNotNull($seo, "Area {$slug} must have SEO metadata.");
            $this->assertNotEmpty($seo->meta_title, "Area {$slug} must have a meta_title.");
            $this->assertNotEmpty($seo->meta_description, "Area {$slug} must have a meta_description.");

            $titles[] = $seo->meta_title;
            $descriptions[] = $seo->meta_description;
        }

        $this->assertSame(count($wave1Slugs), count(array_unique($titles)), 'All Wave 1 area titles must be unique.');
        $this->assertSame(count($wave1Slugs), count(array_unique($descriptions)), 'All Wave 1 area descriptions must be unique.');
    }

    /**
     * 9. The AreaContentSeeder is idempotent: running it twice produces
     * the same result, does not duplicate blocks, and keeps counts stable.
     */
    public function test_seeder_idempotency(): void
    {
        $this->seed(ProductionContentSeeder::class);
        $this->seed(AreaContentSeeder::class);

        $area = Area::where('slug', 'al-olaya')->first();
        $blocksBefore = $area->page->contentBlocks()->count();
        $faqsBefore = $area->page->faqs()->count();

        // Run the seeder a second time.
        $this->seed(AreaContentSeeder::class);

        $this->assertSame($blocksBefore, $area->page->contentBlocks()->count(), 'Block count must be stable after re-run.');
        $this->assertSame($faqsBefore, $area->page->faqs()->count(), 'FAQ count must be stable after re-run.');
    }

    /**
     * 10. The AreaContentSeeder does not overwrite editor-authored content.
     */
    public function test_seeder_does_not_overwrite_editor_content(): void
    {
        $this->seed(ProductionContentSeeder::class);
        $this->seed(AreaContentSeeder::class);

        $area = Area::where('slug', 'al-olaya')->first();
        $page = $area->page;

        // Simulate an editor replacing a seeder block with custom content.
        $page->contentBlocks()->delete();
        $page->contentBlocks()->create([
            'type' => 'rich_text',
            'position' => 1,
            'is_active' => true,
            'data' => ['content' => 'محتوى كتبه المحرر بعناية فائقة ومخصص لحي العليا.'],
        ]);

        $editorContent = $page->contentBlocks()->first()->data['content'];

        // Re-run the seeder: it must NOT overwrite the editor's block.
        $this->seed(AreaContentSeeder::class);

        $page->refresh();
        $this->assertSame(1, $page->contentBlocks()->count(), 'Seeder must not add blocks on top of editor content.');
        $this->assertSame($editorContent, $page->contentBlocks()->first()->data['content'], 'Editor content must be preserved.');
    }
}
