<?php

namespace Tests\Feature\Seo\Concerns;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Area;
use App\Models\ContentBlock;
use App\Models\Media;
use App\Models\Page;
use App\Models\Project;
use App\Models\SeoMetadata;
use App\Models\Service;

/**
 * Shared fixture builders for the SEO Engine test suite. Each helper
 * produces the minimum real data needed to satisfy a specific scenario -
 * never more - so a test failure points at exactly the rule under test.
 */
trait BuildsSeoFixtures
{
    /**
     * A Service page that satisfies every Publishing Gate check (zero
     * errors, zero warnings): valid title/slug, a linked Service with a
     * captioned featured image, an attached Area and Project (both related
     * content and internal-link inbound signal), real content plus a CTA
     * block, and complete SEO metadata. Saving it as Published should
     * never be reverted by PageObserver.
     */
    protected function createCompliantServicePage(string $slug = 'carpet-cleaning-riyadh', PageStatus $status = PageStatus::Published): Page
    {
        $media = Media::factory()->create(['alt_text' => 'صورة خدمة تنظيف السجاد']);
        $service = Service::factory()->create(['featured_media_id' => $media->id]);
        $area = Area::factory()->create();
        $project = Project::factory()->create();
        $service->areas()->attach($area);
        $service->projects()->attach($project);

        $page = Page::factory()->create([
            'type' => PageType::Service,
            'title' => 'تنظيف السجاد بالرياض',
            'slug' => $slug,
            'status' => PageStatus::Draft,
        ]);
        $service->page()->save($page);

        ContentBlock::factory()->for($page)->create([
            'type' => 'rich_text',
            'data' => ['content' => 'نقدم خدمة تنظيف السجاد الاحترافية في جميع أنحاء الرياض باستخدام معدات حديثة وفريق مدرب.'],
        ]);
        ContentBlock::factory()->for($page)->create([
            'type' => 'cta',
            'data' => ['label' => 'اطلب الخدمة الآن'],
        ]);

        SeoMetadata::factory()->for($page)->create([
            'meta_title' => 'تنظيف السجاد بالرياض | Vibe Clean Pro',
            'meta_description' => 'خدمة تنظيف سجاد احترافية بالرياض.',
        ]);

        $page->status = $status;
        $page->save();

        return $page->fresh(['contentBlocks', 'seoMetadata', 'pageable']);
    }

    /**
     * An Area page that satisfies every Publishing Gate check, including
     * the Area-only Local Page Quality Gate checks: a linked Service, a
     * real Project in the area, and enough independent body text (>=150
     * characters) to not read as thin content.
     */
    protected function createCompliantAreaPage(string $slug = 'al-malaz', string $title = 'تنظيف المنازل في حي الملز', PageStatus $status = PageStatus::Published): Page
    {
        $area = Area::factory()->create();
        $service = Service::factory()->create();
        $area->services()->attach($service);
        Project::factory()->create(['area_id' => $area->id]);

        $page = Page::factory()->create([
            'type' => PageType::Area,
            'title' => $title,
            'slug' => $slug,
            'status' => PageStatus::Draft,
        ]);
        $area->page()->save($page);

        // Interpolates $title so two pages built from different slugs/titles
        // are not accidentally near-duplicates of each other by default -
        // callers that specifically want two near-identical Area pages (see
        // AreaPageQualityGateTest) pass the same body text explicitly.
        ContentBlock::factory()->for($page)->create([
            'type' => 'rich_text',
            'data' => ['content' => str_repeat("نخدم منطقة {$title} بفريق محلي متخصص في التنظيف المنزلي والتجاري بخبرة طويلة في المنطقة. ", 3)],
        ]);
        ContentBlock::factory()->for($page)->create([
            'type' => 'cta',
            'data' => ['label' => "اطلب الخدمة في {$title}"],
        ]);

        SeoMetadata::factory()->for($page)->create([
            'meta_title' => $title.' | Vibe Clean Pro',
            'meta_description' => "خدمات تنظيف في {$title} بالرياض.",
        ]);

        $page->status = $status;
        $page->save();

        return $page->fresh(['contentBlocks', 'seoMetadata', 'pageable']);
    }

    /**
     * A bare, minimal Page with no entity, content, or metadata - useful
     * anywhere the test only cares about status/indexability plumbing and
     * would otherwise be noise-heavy to build via the compliant helpers.
     */
    protected function makeBarePage(array $overrides = []): Page
    {
        return Page::factory()->create($overrides);
    }
}
