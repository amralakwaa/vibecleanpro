<?php

namespace Tests\Feature\Seo;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Area;
use App\Models\ContentBlock;
use App\Models\InternalLink;
use App\Models\Media;
use App\Models\Page;
use App\Models\SeoMetadata;
use App\Models\Service;
use App\Seo\InternalLinkAnalyzer;
use App\Seo\OrphanPageAnalyzer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

class InternalLinkingAndOrphanTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    public function test_explicit_inbound_count_only_counts_active_internal_links(): void
    {
        $target = $this->createCompliantServicePage(slug: 'link-target');
        $source = $this->createCompliantServicePage(slug: 'link-source');

        InternalLink::factory()->create(['from_page_id' => $source->id, 'to_page_id' => $target->id, 'is_active' => true]);

        $this->assertSame(1, app(InternalLinkAnalyzer::class)->explicitInboundCount($target->fresh()));
    }

    public function test_an_inactive_internal_link_does_not_count_as_inbound_signal(): void
    {
        $target = $this->createCompliantServicePage(slug: 'link-target-inactive');
        $source = $this->createCompliantServicePage(slug: 'link-source-inactive');

        InternalLink::factory()->create(['from_page_id' => $source->id, 'to_page_id' => $target->id, 'is_active' => false]);

        $this->assertSame(0, app(InternalLinkAnalyzer::class)->explicitInboundCount($target->fresh()));
    }

    public function test_relational_inbound_count_counts_real_content_graph_relations(): void
    {
        // createCompliantServicePage already attaches one Area and one
        // Project to its Service - that alone is real inbound signal, with
        // no explicit InternalLink row needed.
        $page = $this->createCompliantServicePage();

        $this->assertSame(2, app(InternalLinkAnalyzer::class)->relationalInboundCount($page));
    }

    public function test_inbound_count_combines_explicit_and_relational_signal(): void
    {
        $target = $this->createCompliantServicePage(slug: 'combined-target');
        $source = $this->createCompliantServicePage(slug: 'combined-source');
        InternalLink::factory()->create(['from_page_id' => $source->id, 'to_page_id' => $target->id]);

        // 2 relational (area + project attached by the fixture) + 1 explicit.
        $this->assertSame(3, app(InternalLinkAnalyzer::class)->inboundCount($target->fresh()));
    }

    public function test_a_published_indexable_page_with_zero_inbound_signal_is_an_orphan(): void
    {
        $media = Media::factory()->create();
        $service = Service::factory()->create(['featured_media_id' => $media->id]);
        $page = Page::factory()->create([
            'type' => PageType::Service,
            'slug' => 'truly-orphaned',
            'status' => PageStatus::Draft,
        ]);
        $service->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'محتوى حقيقي هنا.']]);
        ContentBlock::factory()->for($page)->create(['type' => 'cta', 'data' => ['label' => 'اطلب']]);
        SeoMetadata::factory()->for($page)->create();
        $page->status = PageStatus::Published;
        $page->save();

        $orphanIds = app(OrphanPageAnalyzer::class)->orphans()->pluck('id');

        $this->assertTrue($orphanIds->contains($page->fresh()->id));
    }

    public function test_a_page_with_real_inbound_signal_is_never_an_orphan(): void
    {
        $page = $this->createCompliantServicePage(slug: 'well-linked');

        $orphanIds = app(OrphanPageAnalyzer::class)->orphans()->pluck('id');

        $this->assertFalse($orphanIds->contains($page->id));
    }

    public function test_a_draft_page_is_never_counted_as_an_orphan_since_orphans_only_apply_to_published_pages(): void
    {
        $this->makeBarePage(['status' => PageStatus::Draft, 'slug' => 'draft-not-orphan-relevant']);

        $orphanSlugs = app(OrphanPageAnalyzer::class)->orphans()->pluck('slug');

        $this->assertFalse($orphanSlugs->contains('draft-not-orphan-relevant'));
    }

    public function test_an_area_page_with_no_attached_services_projects_articles_or_offers_has_zero_relational_inbound_signal(): void
    {
        $area = Area::factory()->create();
        $page = Page::factory()->create(['type' => PageType::Area, 'slug' => 'unlinked-area']);
        $area->page()->save($page);

        $this->assertSame(0, app(InternalLinkAnalyzer::class)->relationalInboundCount($page->fresh(['pageable'])));
    }
}
