<?php

namespace Tests\Feature;

use App\Enums\MediaStage;
use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Area;
use App\Models\ContentBlock;
use App\Models\Media;
use App\Models\Page;
use App\Models\Project;
use App\Models\SeoMetadata;
use App\Models\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectsIndexTest extends TestCase
{
    use RefreshDatabase;

    private function createPublishedProject(string $slug, ?Area $area = null, array $attributes = []): Project
    {
        $project = Project::factory()->create([...$attributes, 'area_id' => $area?->id, 'title' => $slug]);
        $page = Page::factory()->create(['type' => PageType::Project, 'slug' => $slug]);
        $project->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'نص.']]);
        SeoMetadata::factory()->for($page)->create();
        $page->update(['status' => PageStatus::Published]);

        return $project;
    }

    public function test_only_published_projects_are_listed(): void
    {
        $this->createPublishedProject('published-project');
        Project::factory()->create(['title' => 'unpublished-project']);

        $html = $this->get('/projects')->getContent();

        $this->assertStringContainsString('published-project', $html);
        $this->assertStringNotContainsString('unpublished-project', $html);
    }

    public function test_filtering_by_area_narrows_the_results_without_changing_the_canonical(): void
    {
        $area = Area::factory()->create();
        $this->createPublishedProject('project-in-area', $area);
        $this->createPublishedProject('project-elsewhere');

        $response = $this->get('/projects?area='.$area->id);

        $response->assertOk();
        $response->assertSee('project-in-area');
        $response->assertDontSee('project-elsewhere');

        // Phase 4 policy: a filter is a display convenience, never its own
        // canonical/indexable variant.
        $this->assertStringContainsString('<link rel="canonical" href="'.rtrim(config('app.url'), '/').'/projects">', $response->getContent());
    }

    public function test_the_empty_states_are_customer_facing_and_distinguish_a_filter_from_an_empty_gallery(): void
    {
        $bare = $this->get('/projects')->assertOk();
        $bare->assertSee('نجهّز معرض أعمالنا');
        $bare->assertDontSee('منشورة');

        $this->createPublishedProject('exists-elsewhere');
        $area = Area::factory()->create();

        $filtered = $this->get('/projects?area='.$area->id)->assertOk();
        $filtered->assertSee('لا توجد أعمال مطابقة لهذا الاختيار');
        $filtered->assertSee('href="'.route('public.projects.index').'"', false);
        $filtered->assertDontSee('exists-elsewhere');
    }

    public function test_a_featured_project_leads_page_one_exactly_once_and_never_leads_a_later_page(): void
    {
        $this->createPublishedProject('lead-featured', attributes: ['is_featured' => true, 'completed_at' => now()->subYear()]);
        foreach (range(1, 12) as $i) {
            $this->createPublishedProject("plain-$i", attributes: ['completed_at' => now()->subDays($i)]);
        }

        $first = $this->get('/projects')->assertOk()->getContent();
        $this->assertStringContainsString('مشروع مميز', $first);
        $this->assertSame(1, preg_match_all('/>\s*lead-featured\s*</u', $first));
        $this->assertStringContainsString('المزيد من أعمالنا', $first);
        $this->assertLessThan(mb_strpos($first, 'plain-1'), mb_strpos($first, 'lead-featured'));

        // Pagination is preserved: 13 projects, 12 per page.
        $this->assertStringContainsString('?page=2', $first);
        $second = $this->get('/projects?page=2')->assertOk()->getContent();
        $this->assertStringNotContainsString('مشروع مميز', $second);
        $this->assertStringContainsString('plain-12', $second);

        // Even when every project is featured, only page one gets a lead.
        Project::query()->update(['is_featured' => true]);
        $this->assertStringNotContainsString('مشروع مميز', $this->get('/projects?page=2')->getContent());
    }

    public function test_an_ordinary_first_project_is_not_dressed_up_as_featured(): void
    {
        $this->createPublishedProject('ordinary-first');

        $html = $this->get('/projects')->assertOk()->getContent();

        $this->assertStringNotContainsString('مشروع مميز', $html);
        $this->assertStringContainsString('أعمال منفذة', $html);
    }

    public function test_the_before_and_after_hint_appears_only_when_both_stages_really_exist(): void
    {
        $both = $this->createPublishedProject('both-stages');
        $both->media()->attach(Media::factory()->create(['alt_text' => 'صورة-بعد-حقيقية'])->id, ['stage' => MediaStage::After->value, 'sort_order' => 0]);
        $both->media()->attach(Media::factory()->create()->id, ['stage' => MediaStage::Before->value, 'sort_order' => 0]);

        $html = $this->get('/projects')->assertOk()->getContent();
        $this->assertStringContainsString('قبل وبعد', $html);
        $this->assertStringContainsString('alt="صورة-بعد-حقيقية"', $html);

        $both->media()->detach();
        $both->media()->attach(Media::factory()->create()->id, ['stage' => MediaStage::After->value, 'sort_order' => 0]);
        $this->assertStringNotContainsString('قبل وبعد', $this->get('/projects')->getContent());
    }

    public function test_metadata_comes_only_from_real_relations_and_columns(): void
    {
        $area = Area::factory()->create(['name' => 'منطقة-حقيقية-للمشروع']);
        $service = Service::factory()->create(['name' => 'خدمة-حقيقية-للمشروع']);
        $project = $this->createPublishedProject('rich-meta', $area, ['completed_at' => '2026-05-10']);
        $project->services()->attach($service);
        $this->createPublishedProject('bare-meta', attributes: ['completed_at' => null, 'summary' => null]);

        $html = $this->get('/projects')->assertOk()->getContent();

        $this->assertStringContainsString('منطقة-حقيقية-للمشروع', $html);
        $this->assertStringContainsString('خدمة-حقيقية-للمشروع', $html);
        $this->assertSame(1, substr_count($html, '<time datetime="2026-05-10">'));
        $this->assertSame(1, preg_match_all('/<time datetime=/u', $html));
        $this->assertDoesNotMatchRegularExpression('/ريال|placeholder|لوريم/u', $html);
    }

    public function test_the_filter_selects_keep_the_active_choice_and_offer_a_real_all_option(): void
    {
        $area = Area::factory()->create();
        $areaPage = Page::factory()->create(['type' => PageType::Area, 'slug' => 'filter-area']);
        $area->page()->save($areaPage);
        ContentBlock::factory()->for($areaPage)->create(['type' => 'rich_text', 'data' => ['content' => 'نص.']]);
        $areaPage->update(['status' => PageStatus::Published]);
        $this->createPublishedProject('in-area', $area);

        $html = $this->get('/projects?area='.$area->id)->assertOk()->getContent();

        $this->assertStringContainsString('<option value="'.$area->id.'" selected', $html);
        $this->assertMatchesRegularExpression('/<option value=""\s*>كل المناطق<\/option>/u', $html);
        $this->assertStringContainsString('إزالة التصفية', $html);
    }

    public function test_the_index_triggers_no_lazy_loading(): void
    {
        $area = Area::factory()->create();
        $service = Service::factory()->create();
        foreach (range(1, 3) as $i) {
            $project = $this->createPublishedProject("lazy-$i", $area, ['is_featured' => $i === 1]);
            $project->services()->attach($service);
            $project->media()->attach(Media::factory()->create()->id, ['stage' => MediaStage::After->value, 'sort_order' => 0]);
        }

        $violations = [];
        Model::preventLazyLoading(true);
        Model::handleLazyLoadingViolationUsing(function ($model, $relation) use (&$violations) {
            $violations[] = $model::class.'::'.$relation;
        });

        try {
            $this->get('/projects')->assertOk();
            $this->get('/projects?service='.$service->id.'&area='.$area->id)->assertOk();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertSame([], $violations);
    }
}
