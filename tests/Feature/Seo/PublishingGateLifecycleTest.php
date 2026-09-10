<?php

namespace Tests\Feature\Seo;

use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\User;
use App\Seo\IndexabilityEvaluator;
use App\Seo\SitemapGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

/**
 * End-to-end coverage of the Draft -> Review -> Published lifecycle as
 * actually enforced through PageObserver (app/Observers/PageObserver.php),
 * not just PublishingGate::evaluate() in isolation (see
 * PublishingGateChecksTest for that).
 */
class PublishingGateLifecycleTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    public function test_a_draft_page_is_never_indexable_regardless_of_its_content(): void
    {
        $page = $this->createCompliantServicePage(status: PageStatus::Draft);

        $decision = app(IndexabilityEvaluator::class)->evaluate($page);

        $this->assertFalse($decision->indexable);
    }

    public function test_a_review_page_is_never_indexable_regardless_of_its_content(): void
    {
        $page = $this->createCompliantServicePage(status: PageStatus::Review);

        $decision = app(IndexabilityEvaluator::class)->evaluate($page);

        $this->assertFalse($decision->indexable);
    }

    public function test_a_fully_compliant_page_saved_as_published_stays_published_and_becomes_indexable(): void
    {
        $page = $this->createCompliantServicePage(status: PageStatus::Published);

        $this->assertSame(PageStatus::Published, $page->fresh()->status);
        $this->assertTrue(app(IndexabilityEvaluator::class)->evaluate($page->fresh(['seoMetadata']))->indexable);
    }

    public function test_publishing_a_page_with_a_real_technical_error_reverts_it_to_draft(): void
    {
        $page = $this->createCompliantServicePage(status: PageStatus::Draft);
        // Break a genuine technical (error-level) rule: an invalid custom
        // canonical.
        $page->seoMetadata->forceFill(['canonical_url' => 'javascript:alert(1)'])->save();

        $page->status = PageStatus::Published;
        $page->save();

        $this->assertSame(PageStatus::Draft, $page->fresh()->status, 'A technical ERROR must block the page from staying Published.');
    }

    public function test_a_reverted_page_is_not_indexable_and_does_not_appear_in_the_sitemap(): void
    {
        $page = $this->createCompliantServicePage(status: PageStatus::Draft, slug: 'will-be-reverted');
        $page->seoMetadata->forceFill(['canonical_url' => 'javascript:alert(1)'])->save();

        $page->status = PageStatus::Published;
        $page->save();

        $this->assertFalse(app(IndexabilityEvaluator::class)->evaluate($page->fresh(['seoMetadata']))->indexable);
        $locs = app(SitemapGenerator::class)->entries()->pluck('loc');
        $this->assertFalse($locs->contains(fn ($loc) => str_contains($loc, 'will-be-reverted')));
    }

    public function test_an_editorial_warning_alone_never_blocks_publishing(): void
    {
        $page = $this->createCompliantServicePage(status: PageStatus::Draft);
        // Missing meta description is a WARNING, not an ERROR.
        $page->seoMetadata->update(['meta_description' => null]);

        $page->status = PageStatus::Published;
        $page->save();

        $this->assertSame(PageStatus::Published, $page->fresh()->status);
    }

    public function test_removing_the_blocking_error_and_republishing_succeeds(): void
    {
        $page = $this->createCompliantServicePage(status: PageStatus::Draft);
        $page->seoMetadata->forceFill(['canonical_url' => 'javascript:alert(1)'])->save();

        $page->status = PageStatus::Published;
        $page->save();
        $this->assertSame(PageStatus::Draft, $page->fresh()->status, 'Sanity check: should have been reverted first.');

        // Fix the error, then retry.
        $page->fresh()->seoMetadata->update(['canonical_url' => null]);
        $page->fresh()->update(['status' => PageStatus::Published]);

        $this->assertSame(PageStatus::Published, $page->fresh()->status);
    }

    public function test_the_publishing_gate_revert_does_not_recurse_through_the_saved_event(): void
    {
        $page = $this->createCompliantServicePage(status: PageStatus::Draft);
        $page->seoMetadata->forceFill(['canonical_url' => 'javascript:alert(1)'])->save();

        $firedCount = 0;
        Event::listen('eloquent.saved: '.Page::class, function () use (&$firedCount) {
            $firedCount++;
        });

        $page->status = PageStatus::Published;
        $page->save();

        // One real save (Published, rejected) plus the Observer's own
        // saveQuietly() revert would show up here as 2 only if saveQuietly
        // still dispatched events - it must not, or a failing gate would
        // recurse indefinitely instead of settling once.
        $this->assertSame(1, $firedCount, 'saveQuietly() must not re-dispatch the saved event (that would risk infinite recursion).');
        $this->assertSame(PageStatus::Draft, $page->fresh()->status);
    }

    public function test_a_super_admin_cannot_bypass_a_technical_error_by_saving_directly(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('Super Admin', 'web'));
        $this->actingAs($admin);

        $page = $this->createCompliantServicePage(status: PageStatus::Draft);
        $page->seoMetadata->forceFill(['canonical_url' => 'javascript:alert(1)'])->save();

        $page->status = PageStatus::Published;
        $page->save();

        // PageObserver enforces the gate unconditionally - it never checks
        // who the acting user is, so even a Super Admin's save is reverted.
        $this->assertSame(PageStatus::Draft, $page->fresh()->status);
    }

    public function test_the_draft_review_published_workflow_works_end_to_end(): void
    {
        $page = $this->createCompliantServicePage(status: PageStatus::Draft);
        $this->assertSame(PageStatus::Draft, $page->fresh()->status);
        $this->assertFalse(app(IndexabilityEvaluator::class)->evaluate($page->fresh(['seoMetadata']))->indexable);

        $page->update(['status' => PageStatus::Review]);
        $this->assertSame(PageStatus::Review, $page->fresh()->status);
        $this->assertFalse(app(IndexabilityEvaluator::class)->evaluate($page->fresh(['seoMetadata']))->indexable);

        $page->update(['status' => PageStatus::Published]);
        $this->assertSame(PageStatus::Published, $page->fresh()->status);
        $this->assertTrue(app(IndexabilityEvaluator::class)->evaluate($page->fresh(['seoMetadata']))->indexable);
    }

    public function test_moving_a_published_page_to_draft_immediately_removes_it_from_indexability_and_sitemap(): void
    {
        $page = $this->createCompliantServicePage(status: PageStatus::Published, slug: 'about-to-unpublish');
        $this->assertTrue(app(SitemapGenerator::class)->entries()->pluck('loc')->contains(fn ($loc) => str_contains($loc, 'about-to-unpublish')));

        $page->update(['status' => PageStatus::Draft]);

        $this->assertFalse(app(IndexabilityEvaluator::class)->evaluate($page->fresh(['seoMetadata']))->indexable);
        $this->assertFalse(app(SitemapGenerator::class)->entries()->pluck('loc')->contains(fn ($loc) => str_contains($loc, 'about-to-unpublish')));
    }

    public function test_moving_a_published_page_to_archived_immediately_removes_it_from_indexability_and_sitemap(): void
    {
        $page = $this->createCompliantServicePage(status: PageStatus::Published, slug: 'about-to-archive');

        $page->update(['status' => PageStatus::Archived]);

        $this->assertFalse(app(IndexabilityEvaluator::class)->evaluate($page->fresh(['seoMetadata']))->indexable);
        $this->assertFalse(app(SitemapGenerator::class)->entries()->pluck('loc')->contains(fn ($loc) => str_contains($loc, 'about-to-archive')));
    }

    public function test_archiving_a_published_page_returns_410_instead_of_200(): void
    {
        $this->createCompliantServicePage(status: PageStatus::Published, slug: 'to-be-archived');
        $page = Page::query()->where('slug', 'to-be-archived')->firstOrFail();

        $page->update(['status' => PageStatus::Archived]);

        $this->get('/services/to-be-archived')->assertStatus(410);
    }
}
