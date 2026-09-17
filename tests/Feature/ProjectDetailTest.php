<?php

namespace Tests\Feature;

use App\Enums\MediaStage;
use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Area;
use App\Models\ContentBlock;
use App\Models\Faq;
use App\Models\Media;
use App\Models\Page;
use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\AssertsNoEmptyState;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

/**
 * Project Detail is a case study: the photographs are the content. These
 * tests pin the media honesty rules - what renders is exactly the
 * project's own project_media rows, in their editor-given stage and
 * order, paired only when a real pair exists, and never padded.
 */
class ProjectDetailTest extends TestCase
{
    use AssertsNoEmptyState, BuildsSeoFixtures, RefreshDatabase;

    public function test_before_and_after_photos_render_as_a_genuine_pair(): void
    {
        $project = $this->publishedProject('villa-pair');
        $this->attach($project, MediaStage::Before, 'قبل-الصالة');
        $this->attach($project, MediaStage::After, 'بعد-الصالة');

        $content = $this->get('/projects/villa-pair')->assertOk()->getContent();

        $this->assertStringContainsString('قبل وبعد', $content);
        $this->assertStringContainsString('قبل-الصالة', $content);
        $this->assertStringContainsString('بعد-الصالة', $content);
        // Neither stage is left over, so the "more" strips never appear.
        $this->assertStringNotContainsString('المزيد من النتيجة', $content);
    }

    public function test_pairs_follow_the_editor_sort_order_and_never_cross_match(): void
    {
        $project = $this->publishedProject('villa-order');
        $this->attach($project, MediaStage::Before, 'قبل-الثاني', sortOrder: 1);
        $this->attach($project, MediaStage::Before, 'قبل-الأول', sortOrder: 0);
        $this->attach($project, MediaStage::After, 'بعد-الأول', sortOrder: 0);
        $this->attach($project, MediaStage::After, 'بعد-الثاني', sortOrder: 1);

        $content = $this->get('/projects/villa-order')->assertOk()->getContent();

        // The hero legitimately shows the first "after" photo at the top
        // of the page, so ordering is asserted INSIDE the pairs section.
        $pairsSection = mb_substr($content, mb_strpos($content, 'قبل وبعد'));

        // The first pair must be (قبل-الأول, بعد-الأول) even though قبل-الثاني
        // was inserted first - stage order comes from pivot.sort_order.
        $firstBefore = mb_strpos($pairsSection, 'قبل-الأول');
        $firstAfter = mb_strpos($pairsSection, 'بعد-الأول');
        $secondBefore = mb_strpos($pairsSection, 'قبل-الثاني');

        $this->assertLessThan($firstAfter, $firstBefore, 'First before must precede first after.');
        $this->assertLessThan($secondBefore, $firstAfter, 'The whole first pair must precede the second pair.');
    }

    public function test_an_after_photo_with_no_before_is_shown_as_the_result_and_never_given_a_fake_before(): void
    {
        $project = $this->publishedProject('villa-after-only');
        $this->attach($project, MediaStage::After, 'بعد-فقط');

        $content = $this->get('/projects/villa-after-only')->assertOk()->getContent();

        $this->assertStringContainsString('بعد-فقط', $content);
        $this->assertStringContainsString('النتيجة', $content);
        $this->assertStringNotContainsString('قبل وبعد', $content);
        // The word "قبل" appears only inside the heading prose, never as
        // a pair label - so the pair label markup must be absent.
        $this->assertStringNotContainsString('text-neutral-500">قبل</p>', $content);
    }

    public function test_a_before_photo_with_no_after_is_shown_honestly_under_its_own_stage(): void
    {
        $project = $this->publishedProject('villa-before-only');
        $this->attach($project, MediaStage::Before, 'قبل-فقط');

        $content = $this->get('/projects/villa-before-only')->assertOk()->getContent();

        $this->assertStringContainsString('قبل-فقط', $content);
        $this->assertStringNotContainsString('قبل وبعد', $content);
        $this->assertStringNotContainsString('النتيجة', $content);
    }

    public function test_during_photos_render_as_their_own_process_sequence(): void
    {
        $project = $this->publishedProject('villa-during');
        $this->attach($project, MediaStage::During, 'أثناء-١', sortOrder: 0);
        $this->attach($project, MediaStage::During, 'أثناء-٢', sortOrder: 1);

        $content = $this->get('/projects/villa-during')->assertOk()->getContent();

        $this->assertStringContainsString('أثناء العمل', $content);
        $this->assertLessThan(mb_strpos($content, 'أثناء-٢'), mb_strpos($content, 'أثناء-١'));
    }

    public function test_a_project_with_no_media_renders_no_evidence_section_and_no_empty_state(): void
    {
        $this->publishedProject('villa-no-media');

        $content = $this->get('/projects/villa-no-media')->assertOk()->getContent();

        $this->assertStringNotContainsString('الدليل', $content);
        $this->assertStringNotContainsString('أثناء العمل', $content);
        $this->assertStringNotContainsString('قبل وبعد', $content);
        $this->assertNoCustomerFacingEmptyState($content);
    }

    public function test_the_context_rail_links_the_service_and_a_published_area_but_never_an_unpublished_one(): void
    {
        $servicePage = $this->createCompliantServicePage(slug: 'ctx-service');
        $areaPage = $this->createCompliantAreaPage(slug: 'ctx-area', title: 'منطقة منشورة للسياق');

        $project = $this->publishedProject('villa-context', area: $areaPage->pageable);
        $project->services()->attach($servicePage->pageable);
        $project->update(['completed_at' => '2026-03-15']);

        $content = $this->get('/projects/villa-context')->assertOk()->getContent();

        $this->assertStringContainsString('href="'.route('public.service', 'ctx-service').'"', $content);
        $this->assertStringContainsString('href="'.route('public.area', 'ctx-area').'"', $content);
        $this->assertStringContainsString('datetime="2026-03-15"', $content);

        // An area whose page is NOT published renders as plain text only.
        $draftArea = Area::factory()->create(['name' => 'منطقة-مسودة-فقط']);
        Page::factory()->create(['type' => PageType::Area, 'slug' => 'draft-ctx-area', 'status' => PageStatus::Draft])
            ->pageable()->associate($draftArea)->save();
        $draftProject = $this->publishedProject('villa-draft-area', area: $draftArea);

        $draftContent = $this->get('/projects/villa-draft-area')->assertOk()->getContent();

        $this->assertStringContainsString('منطقة-مسودة-فقط', $draftContent);
        $this->assertStringNotContainsString('href="'.route('public.area', 'draft-ctx-area').'"', $draftContent);
    }

    public function test_related_projects_come_only_from_the_same_area_and_only_when_published(): void
    {
        $area = Area::factory()->create();
        $this->publishedProject('villa-main', area: $area);
        $this->publishedProject('villa-sibling', area: $area, title: 'مشروع شقيق منشور');

        // Same area but unpublished: must not appear.
        $draft = Project::factory()->create(['area_id' => $area->id, 'title' => 'مشروع شقيق مسودة']);
        $draft->page()->save(Page::factory()->create(['type' => PageType::Project, 'slug' => 'villa-draft-sibling', 'status' => PageStatus::Draft]));

        // Different area: must not appear.
        $this->publishedProject('villa-elsewhere', area: Area::factory()->create(), title: 'مشروع في منطقة أخرى');

        $content = $this->get('/projects/villa-main')->assertOk()->getContent();

        $this->assertStringContainsString('مشروع شقيق منشور', $content);
        $this->assertStringNotContainsString('مشروع شقيق مسودة', $content);
        $this->assertStringNotContainsString('مشروع في منطقة أخرى', $content);
    }

    public function test_the_faq_block_renders_once_and_before_the_final_cta(): void
    {
        $project = $this->publishedProject('villa-faq');
        $page = $project->page;
        ContentBlock::factory()->for($page)->create(['type' => 'faq', 'position' => 1, 'data' => ['heading' => 'أسئلة عن هذا المشروع']]);
        Faq::factory()->for($page)->create(['question' => 'كم استغرق؟', 'answer' => 'يومان.']);

        $content = $this->get('/projects/villa-faq')->assertOk()->getContent();

        $this->assertSame(1, substr_count($content, 'أسئلة عن هذا المشروع'));
        $this->assertSame(1, substr_count($content, 'كم استغرق؟'));
        $this->assertStringContainsString('يومان.', $content);
        $this->assertLessThan(
            mb_strpos($content, 'نتيجة مشابهة لمساحتك'),
            mb_strpos($content, 'أسئلة عن هذا المشروع'),
        );
    }

    public function test_the_cta_prefills_the_quote_with_the_projects_service_and_area(): void
    {
        $servicePage = $this->createCompliantServicePage(slug: 'cta-service');
        $areaPage = $this->createCompliantAreaPage(slug: 'cta-area');
        $project = $this->publishedProject('villa-cta', area: $areaPage->pageable);
        $project->services()->attach($servicePage->pageable);

        $content = $this->get('/projects/villa-cta')->assertOk()->getContent();

        // Inside an href the query separator is HTML-escaped, so compare
        // against the escaped form the browser actually receives.
        $expected = e(route('public.quote', ['service' => $servicePage->pageable->id, 'area' => $areaPage->pageable->id]));
        // Hero CTA and the closing band - two, not one per section.
        $this->assertSame(2, substr_count($content, $expected));
    }

    public function test_the_page_triggers_no_lazy_loading(): void
    {
        $area = Area::factory()->create();
        $servicePage = $this->createCompliantServicePage(slug: 'lazy-service');
        $project = $this->publishedProject('villa-lazy', area: $area);
        $project->services()->attach($servicePage->pageable);
        $this->attach($project, MediaStage::Before, 'b');
        $this->attach($project, MediaStage::After, 'a');
        $this->publishedProject('villa-lazy-sibling', area: $area);

        $violations = [];
        Model::preventLazyLoading(true);
        Model::handleLazyLoadingViolationUsing(function ($model, $relation) use (&$violations) {
            $violations[] = $model::class.'::'.$relation;
        });

        try {
            $this->get('/projects/villa-lazy')->assertOk();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertSame([], $violations);
    }

    private function publishedProject(string $slug, ?Area $area = null, ?string $title = null): Project
    {
        $project = Project::factory()->create(array_filter([
            'area_id' => $area?->id,
            'title' => $title,
        ]));

        $page = Page::factory()->create([
            'type' => PageType::Project,
            'title' => $title ?? $project->title,
            'slug' => $slug,
            'status' => PageStatus::Draft,
        ]);
        $project->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'تفاصيل المشروع.']]);
        $page->update(['status' => PageStatus::Published]);

        return $project->fresh(['page']);
    }

    private function attach(Project $project, MediaStage $stage, string $altText, int $sortOrder = 0): Media
    {
        $media = Media::factory()->create(['alt_text' => $altText]);
        $project->media()->attach($media->id, ['stage' => $stage->value, 'sort_order' => $sortOrder]);

        return $media;
    }
}
