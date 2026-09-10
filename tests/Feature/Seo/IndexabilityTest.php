<?php

namespace Tests\Feature\Seo;

use App\Enums\PageStatus;
use App\Seo\IndexabilityEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

class IndexabilityTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    private IndexabilityEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->evaluator = app(IndexabilityEvaluator::class);
    }

    public function test_a_missing_page_is_never_indexable(): void
    {
        $decision = $this->evaluator->evaluate(null);

        $this->assertFalse($decision->indexable);
        $this->assertFalse($decision->follow);
        $this->assertContains('page_missing', $decision->reasons);
    }

    public function test_a_soft_deleted_page_is_never_indexable(): void
    {
        $page = $this->makeBarePage(['status' => PageStatus::Published]);
        $page->delete();

        $decision = $this->evaluator->evaluate($page);

        $this->assertFalse($decision->indexable);
        $this->assertContains('page_missing', $decision->reasons);
    }

    public function test_a_draft_page_is_never_indexable(): void
    {
        $page = $this->makeBarePage(['status' => PageStatus::Draft]);

        $decision = $this->evaluator->evaluate($page);

        $this->assertFalse($decision->indexable);
        $this->assertContains('status_draft', $decision->reasons);
    }

    public function test_a_review_page_is_never_indexable(): void
    {
        $page = $this->makeBarePage(['status' => PageStatus::Review]);

        $decision = $this->evaluator->evaluate($page);

        $this->assertFalse($decision->indexable);
        $this->assertContains('status_review', $decision->reasons);
    }

    public function test_an_archived_page_is_never_indexable(): void
    {
        $page = $this->makeBarePage(['status' => PageStatus::Archived]);

        $decision = $this->evaluator->evaluate($page);

        $this->assertFalse($decision->indexable);
        $this->assertContains('status_archived', $decision->reasons);
    }

    public function test_a_published_page_scheduled_in_the_future_is_not_yet_indexable(): void
    {
        $page = $this->makeBarePage([
            'status' => PageStatus::Published,
            'published_at' => now()->addDay(),
        ]);

        $decision = $this->evaluator->evaluate($page);

        $this->assertFalse($decision->indexable);
        $this->assertContains('scheduled_in_future', $decision->reasons);
    }

    public function test_a_published_and_due_page_is_indexable(): void
    {
        $page = $this->createCompliantServicePage();

        $decision = $this->evaluator->evaluate($page);

        $this->assertTrue($decision->indexable);
        $this->assertContains('published_and_eligible', $decision->reasons);
    }

    public function test_manual_noindex_overrides_an_otherwise_eligible_published_page(): void
    {
        $page = $this->createCompliantServicePage();
        $page->seoMetadata->update(['robots_index' => false]);

        $decision = $this->evaluator->evaluate($page->fresh('seoMetadata'));

        $this->assertFalse($decision->indexable);
        $this->assertContains('manual_noindex', $decision->reasons);
    }

    public function test_follow_is_evaluated_independently_of_indexable(): void
    {
        $page = $this->createCompliantServicePage();
        $page->seoMetadata->update(['robots_index' => false, 'robots_follow' => true]);

        $decision = $this->evaluator->evaluate($page->fresh('seoMetadata'));

        $this->assertFalse($decision->indexable);
        $this->assertTrue($decision->follow, 'A noindexed page should still be followable unless follow is explicitly disabled too.');
    }

    public function test_an_editor_can_explicitly_disable_follow_too(): void
    {
        $page = $this->createCompliantServicePage();
        $page->seoMetadata->update(['robots_follow' => false]);

        $decision = $this->evaluator->evaluate($page->fresh('seoMetadata'));

        $this->assertFalse($decision->follow);
    }

    public function test_a_page_with_no_seo_metadata_row_defaults_to_indexable_and_follow(): void
    {
        $page = $this->makeBarePage(['status' => PageStatus::Published]);

        $decision = $this->evaluator->evaluate($page);

        $this->assertTrue($decision->indexable);
        $this->assertTrue($decision->follow);
    }
}
