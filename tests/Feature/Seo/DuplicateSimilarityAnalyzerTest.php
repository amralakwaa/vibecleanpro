<?php

namespace Tests\Feature\Seo;

use App\Enums\PageStatus;
use App\Models\Page;
use App\Seo\DuplicateSimilarityAnalyzer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

class DuplicateSimilarityAnalyzerTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    public function test_two_area_pages_with_identical_body_text_are_flagged_as_a_similar_pair(): void
    {
        $body = str_repeat('نخدم هذا الحي بفريق محلي متخصص في التنظيف المنزلي والتجاري بخبرة طويلة في المنطقة. ', 4);

        $pageA = $this->createCompliantAreaPage(slug: 'jaccard-a', title: 'حي أ', status: PageStatus::Draft);
        $pageA->contentBlocks()->where('type', 'rich_text')->update(['data' => ['content' => $body]]);

        $pageB = $this->createCompliantAreaPage(slug: 'jaccard-b', title: 'حي ب', status: PageStatus::Draft);
        $pageB->contentBlocks()->where('type', 'rich_text')->update(['data' => ['content' => $body]]);

        Page::query()->whereIn('id', [$pageA->id, $pageB->id])->update(['status' => PageStatus::Published->value]);

        $pairs = app(DuplicateSimilarityAnalyzer::class)->findSimilarAreaPages();

        $this->assertTrue($pairs->contains(fn ($pair) => in_array($pageA->id, [$pair->pageA->id, $pair->pageB->id]) && in_array($pageB->id, [$pair->pageA->id, $pair->pageB->id])
        ));
    }

    public function test_the_reported_score_for_identical_bodies_is_at_or_near_the_maximum(): void
    {
        $body = str_repeat('نخدم هذا الحي بفريق محلي متخصص في التنظيف المنزلي والتجاري بخبرة طويلة في المنطقة. ', 4);

        $pageA = $this->createCompliantAreaPage(slug: 'score-a', title: 'حي أ', status: PageStatus::Draft);
        $pageA->contentBlocks()->where('type', 'rich_text')->update(['data' => ['content' => $body]]);

        $pageB = $this->createCompliantAreaPage(slug: 'score-b', title: 'حي ب', status: PageStatus::Draft);
        $pageB->contentBlocks()->where('type', 'rich_text')->update(['data' => ['content' => $body]]);

        Page::query()->whereIn('id', [$pageA->id, $pageB->id])->update(['status' => PageStatus::Published->value]);

        $pair = app(DuplicateSimilarityAnalyzer::class)->findSimilarAreaPages()->first();

        $this->assertGreaterThanOrEqual(0.6, $pair->score);
        $this->assertLessThanOrEqual(1.0, $pair->score);
    }

    public function test_two_pages_below_the_threshold_are_not_paired(): void
    {
        $pageA = $this->createCompliantAreaPage(slug: 'below-threshold-a', title: 'حي أ', status: PageStatus::Draft);
        $pageA->contentBlocks()->where('type', 'rich_text')->update([
            'data' => ['content' => str_repeat('نقدم في هذا الحي خدمات غسيل السجاد وتلميع الرخام مع فريق مختص بخبرة تمتد لسنوات طويلة. ', 2)],
        ]);

        $pageB = $this->createCompliantAreaPage(slug: 'below-threshold-b', title: 'حي ب', status: PageStatus::Draft);
        $pageB->contentBlocks()->where('type', 'rich_text')->update([
            'data' => ['content' => str_repeat('يوفر فريقنا هنا تعقيم المكيفات وإزالة الحشرات بأحدث الأجهزة وضمان حقيقي على الجودة. ', 2)],
        ]);

        Page::query()->whereIn('id', [$pageA->id, $pageB->id])->update(['status' => PageStatus::Published->value]);

        $pairs = app(DuplicateSimilarityAnalyzer::class)->findSimilarAreaPages();

        $this->assertFalse($pairs->contains(fn ($pair) => in_array($pageA->id, [$pair->pageA->id, $pair->pageB->id]) && in_array($pageB->id, [$pair->pageA->id, $pair->pageB->id])
        ));
    }

    public function test_only_area_pages_are_compared_two_identical_service_pages_are_never_paired_as_areas(): void
    {
        // If Service pages ever leaked into the Area comparison pool, these
        // two identical-content Service pages would score a pair - proving
        // the type filter genuinely excludes them, not just that there was
        // nothing to compare.
        $this->createCompliantServicePage(slug: 'twin-service-a');
        $this->createCompliantServicePage(slug: 'twin-service-b');
        $this->createCompliantAreaPage(slug: 'lone-area');

        $pairs = app(DuplicateSimilarityAnalyzer::class)->findSimilarAreaPages();

        $this->assertCount(0, $pairs);
    }

    public function test_repeated_calls_within_the_same_container_instance_return_the_same_memoized_collection(): void
    {
        $this->createCompliantAreaPage(slug: 'memo-check');
        $analyzer = app(DuplicateSimilarityAnalyzer::class);

        $first = $analyzer->findSimilarAreaPages();
        $second = $analyzer->findSimilarAreaPages();

        $this->assertSame($first, $second, 'The same instance must return the identical memoized Collection object, not recompute.');
    }

    public function test_duplicate_similarity_analyzer_is_bound_as_a_container_singleton(): void
    {
        $this->assertSame(app(DuplicateSimilarityAnalyzer::class), app(DuplicateSimilarityAnalyzer::class));
    }
}
