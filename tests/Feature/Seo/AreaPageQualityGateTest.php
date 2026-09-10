<?php

namespace Tests\Feature\Seo;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Area;
use App\Models\Page;
use App\Models\Service;
use App\Seo\DuplicateSimilarityAnalyzer;
use App\Seo\Enums\CheckSeverity;
use App\Seo\PublishingGate;
use App\Seo\SitemapGenerator;
use App\Seo\ValueObjects\PublishingGateResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

/**
 * The Local Page Quality Gate: extra checks PublishingGate applies only to
 * PageType::Area pages, on top of the generic checklist (see
 * PublishingGateChecksTest). Existing in the database is never enough for
 * an Area page to be considered indexable-worthy on its own.
 */
class AreaPageQualityGateTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    private PublishingGate $gate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gate = app(PublishingGate::class);
    }

    private function severityOf(PublishingGateResult $result, string $key): ?CheckSeverity
    {
        return collect($result->checks)->firstWhere('key', $key)?->severity;
    }

    public function test_the_compliant_area_fixture_has_zero_errors_and_zero_warnings(): void
    {
        $page = $this->createCompliantAreaPage();

        $result = $this->gate->evaluate($page);

        $this->assertTrue($result->canPublish());
        $this->assertCount(0, $result->warnings(), 'Warnings present: '.$result->warnings()->pluck('key')->implode(', '));
    }

    public function test_an_area_with_no_available_service_gets_a_warning(): void
    {
        $page = $this->createCompliantAreaPage();
        $page->pageable->services()->detach();

        $result = $this->gate->evaluate($page->fresh(['contentBlocks', 'pageable']));

        $this->assertSame(CheckSeverity::Warning, $this->severityOf($result, 'local_services'));
        $this->assertTrue($result->canPublish());
    }

    public function test_an_area_with_no_real_project_gets_a_warning(): void
    {
        $page = $this->createCompliantAreaPage();
        $page->pageable->projects()->delete();

        $result = $this->gate->evaluate($page->fresh(['contentBlocks', 'pageable']));

        $this->assertSame(CheckSeverity::Warning, $this->severityOf($result, 'local_projects'));
        $this->assertTrue($result->canPublish());
    }

    public function test_thin_content_under_150_characters_gets_a_warning(): void
    {
        $page = $this->createCompliantAreaPage();
        $page->contentBlocks()->where('type', 'rich_text')->update(['data' => ['content' => 'محتوى قصير جدًا.']]);

        $result = $this->gate->evaluate($page->fresh(['contentBlocks', 'pageable']));

        $this->assertSame(CheckSeverity::Warning, $this->severityOf($result, 'local_content_depth'));
        $this->assertTrue($result->canPublish());
    }

    /**
     * DuplicateSimilarityAnalyzer memoizes per container instance so a
     * request only ever pays for the O(n^2) scan once. Building each page
     * separately as Published (the fixture's default) would let the first
     * page's own save trigger the gate - and therefore the scan - before
     * the second page even exists, freezing a stale snapshot for the rest
     * of the test. Both pages are built as Draft, given their final
     * content, then published together via a single mass update (which
     * fires no model events) so the very first similarity computation
     * sees both pages in their finished state.
     */
    private function publishTogetherWithFreshSimilarityCache(Page $pageA, Page $pageB): PublishingGate
    {
        Page::query()->whereIn('id', [$pageA->id, $pageB->id])->update(['status' => PageStatus::Published->value]);

        app()->forgetInstance(DuplicateSimilarityAnalyzer::class);

        return app(PublishingGate::class);
    }

    public function test_two_near_identical_area_pages_each_get_a_similarity_warning(): void
    {
        $body = str_repeat('نخدم هذا الحي بفريق محلي متخصص في التنظيف المنزلي والتجاري بخبرة طويلة في المنطقة. ', 3);

        $pageA = $this->createCompliantAreaPage(slug: 'area-a', title: 'تنظيف حي أ', status: PageStatus::Draft);
        $pageA->contentBlocks()->where('type', 'rich_text')->update(['data' => ['content' => $body]]);

        $pageB = $this->createCompliantAreaPage(slug: 'area-b', title: 'تنظيف حي ب', status: PageStatus::Draft);
        $pageB->contentBlocks()->where('type', 'rich_text')->update(['data' => ['content' => $body]]);

        $gate = $this->publishTogetherWithFreshSimilarityCache($pageA, $pageB);
        $resultA = $gate->evaluate($pageA->fresh(['contentBlocks', 'pageable']));
        $resultB = $gate->evaluate($pageB->fresh(['contentBlocks', 'pageable']));

        $this->assertSame(CheckSeverity::Warning, $this->severityOf($resultA, 'local_similarity'));
        $this->assertSame(CheckSeverity::Warning, $this->severityOf($resultB, 'local_similarity'));
        $this->assertTrue($resultA->canPublish(), 'Similarity is an internal editorial warning, never a publish-blocking error.');
    }

    public function test_two_genuinely_different_area_pages_get_no_similarity_warning(): void
    {
        $pageA = $this->createCompliantAreaPage(slug: 'area-distinct-a', title: 'تنظيف حي أ', status: PageStatus::Draft);
        $pageA->contentBlocks()->where('type', 'rich_text')->update([
            'data' => ['content' => str_repeat('نقدم في هذا الحي خدمات غسيل السجاد وتلميع الرخام مع فريق مختص بخبرة تمتد لسنوات طويلة في هذا المجال تحديدًا. ', 2)],
        ]);

        $pageB = $this->createCompliantAreaPage(slug: 'area-distinct-b', title: 'تنظيف حي ب', status: PageStatus::Draft);
        $pageB->contentBlocks()->where('type', 'rich_text')->update([
            'data' => ['content' => str_repeat('يوفر فريقنا في هذه المنطقة تعقيم المكيفات وإزالة الحشرات بأحدث الأجهزة وضمان حقيقي على جودة كل عملية تنفذ. ', 2)],
        ]);

        $gate = $this->publishTogetherWithFreshSimilarityCache($pageA, $pageB);
        $resultA = $gate->evaluate($pageA->fresh(['contentBlocks', 'pageable']));
        $resultB = $gate->evaluate($pageB->fresh(['contentBlocks', 'pageable']));

        $this->assertSame(CheckSeverity::Pass, $this->severityOf($resultA, 'local_similarity'));
        $this->assertSame(CheckSeverity::Pass, $this->severityOf($resultB, 'local_similarity'));
    }

    public function test_area_only_checks_never_apply_to_a_service_page(): void
    {
        $page = $this->createCompliantServicePage();

        $result = $this->gate->evaluate($page);

        $this->assertNull($this->severityOf($result, 'local_services'));
        $this->assertNull($this->severityOf($result, 'local_projects'));
        $this->assertNull($this->severityOf($result, 'local_content_depth'));
        $this->assertNull($this->severityOf($result, 'local_similarity'));
    }

    public function test_an_area_existing_in_the_database_alone_produces_no_public_url_sitemap_entry_or_indexability(): void
    {
        Area::factory()->create(['slug' => 'brand-new-area-no-page']);

        $this->assertNull(Page::query()->where('type', PageType::Area)->where('slug', 'brand-new-area-no-page')->first());

        $locs = app(SitemapGenerator::class)->entries()->pluck('loc');
        $this->assertFalse($locs->contains(fn ($loc) => str_contains($loc, 'brand-new-area-no-page')));

        $this->get('/areas/brand-new-area-no-page')->assertNotFound();
    }

    public function test_attaching_a_service_to_an_area_never_auto_creates_a_new_page(): void
    {
        $countBefore = Page::query()->count();

        $area = Area::factory()->create();
        $service = Service::factory()->create();
        $area->services()->attach($service);

        $this->assertSame($countBefore, Page::query()->count(), 'Linking a Service to an Area must never silently create a Service x Area page.');
    }
}
