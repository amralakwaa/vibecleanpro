<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Area;
use App\Models\ContentBlock;
use App\Models\Page;
use App\Models\Project;
use App\Models\SeoMetadata;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectsIndexTest extends TestCase
{
    use RefreshDatabase;

    private function createPublishedProject(string $slug, ?Area $area = null): Project
    {
        $project = Project::factory()->create(['area_id' => $area?->id, 'title' => $slug]);
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

    public function test_the_index_shows_an_empty_state_when_nothing_matches(): void
    {
        $response = $this->get('/projects');

        $response->assertOk();
        $response->assertSee('لا توجد مشاريع مطابقة');
    }
}
