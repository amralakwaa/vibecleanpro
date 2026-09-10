<?php

namespace Tests\Feature\Seo;

use App\Enums\PageStatus;
use App\Models\InternalLink;
use App\Seo\BrokenLinkDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

/**
 * internal_links.to_page_id is a real foreign key with cascadeOnDelete
 * (see the internal_links migration), so a force-deleted target can never
 * leave a truly dangling row - this only ever surfaces the two states that
 * genuinely can occur: a soft-deleted target, and a target that is no
 * longer indexable.
 */
class BrokenLinkDetectorTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    private BrokenLinkDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->detector = app(BrokenLinkDetector::class);
    }

    public function test_an_active_link_to_a_healthy_indexable_page_is_not_reported_broken(): void
    {
        $target = $this->createCompliantServicePage(slug: 'healthy-target');
        $source = $this->createCompliantServicePage(slug: 'healthy-source');
        InternalLink::factory()->create(['from_page_id' => $source->id, 'to_page_id' => $target->id]);

        $reasons = $this->detector->detect()->pluck('link.to_page_id');

        $this->assertFalse($reasons->contains($target->id));
    }

    public function test_a_link_to_a_soft_deleted_target_is_reported_as_target_deleted(): void
    {
        $target = $this->createCompliantServicePage(slug: 'deleted-target');
        $source = $this->createCompliantServicePage(slug: 'source-for-deleted');
        InternalLink::factory()->create(['from_page_id' => $source->id, 'to_page_id' => $target->id]);

        $target->delete();

        $broken = $this->detector->detect()->firstWhere('link.to_page_id', $target->id);

        $this->assertNotNull($broken);
        $this->assertSame('target_deleted', $broken->reason);
    }

    public function test_a_link_to_a_no_longer_indexable_target_is_reported_as_target_not_indexable(): void
    {
        $target = $this->createCompliantServicePage(slug: 'noindex-target');
        $source = $this->createCompliantServicePage(slug: 'source-for-noindex');
        InternalLink::factory()->create(['from_page_id' => $source->id, 'to_page_id' => $target->id]);

        $target->update(['status' => PageStatus::Draft]);

        $broken = $this->detector->detect()->firstWhere('link.to_page_id', $target->id);

        $this->assertNotNull($broken);
        $this->assertSame('target_not_indexable', $broken->reason);
    }

    public function test_an_inactive_internal_link_is_never_reported_since_it_is_not_a_live_link(): void
    {
        $target = $this->createCompliantServicePage(slug: 'inactive-link-target');
        $source = $this->createCompliantServicePage(slug: 'inactive-link-source');
        InternalLink::factory()->create(['from_page_id' => $source->id, 'to_page_id' => $target->id, 'is_active' => false]);

        $target->delete(); // even a broken target on an inactive link is irrelevant.

        $reported = $this->detector->detect()->contains(fn ($broken) => $broken->link->to_page_id === $target->id);

        $this->assertFalse($reported);
    }

    public function test_a_force_deleted_target_leaves_no_internal_link_row_to_report_at_all(): void
    {
        $target = $this->createCompliantServicePage(slug: 'force-deleted-target');
        $source = $this->createCompliantServicePage(slug: 'source-for-force-deleted');
        InternalLink::factory()->create(['from_page_id' => $source->id, 'to_page_id' => $target->id]);

        $target->forceDelete();

        $this->assertSame(0, InternalLink::query()->count(), 'cascadeOnDelete on to_page_id must remove the link row, not leave it dangling.');
    }
}
