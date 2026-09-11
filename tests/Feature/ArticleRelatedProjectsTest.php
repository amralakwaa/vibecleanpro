<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Area;
use App\Models\Article;
use App\Models\ContentBlock;
use App\Models\Page;
use App\Models\Project;
use App\Models\SeoMetadata;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Related projects" on an Article has no direct schema relation (see the
 * Phase 6 report, item 25) - it is purely inferred at render time from the
 * Service/Area relations the Article already has, never stored. Labeled
 * "مشاريع مرتبطة بالموضوع" (topically related), never "مشاريع المقال"
 * (the article's own projects), since the relation is inferred, not real.
 */
class ArticleRelatedProjectsTest extends TestCase
{
    use RefreshDatabase;

    private function publishProject(string $slug, ?Area $area = null): Project
    {
        $project = Project::factory()->create(['area_id' => $area?->id, 'title' => $slug]);
        $page = Page::factory()->create(['type' => PageType::Project, 'slug' => $slug]);
        $project->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'نص.']]);
        SeoMetadata::factory()->for($page)->create();
        $page->update(['status' => PageStatus::Published]);

        return $project;
    }

    private function publishArticle(string $slug): Article
    {
        $article = Article::factory()->create(['title' => $slug]);
        $page = Page::factory()->create(['type' => PageType::Article, 'slug' => $slug]);
        $article->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'محتوى.']]);
        SeoMetadata::factory()->for($page)->create();
        $page->update(['status' => PageStatus::Published]);

        return $article;
    }

    public function test_a_project_sharing_the_articles_area_appears_as_topically_related(): void
    {
        $area = Area::factory()->create();
        $article = $this->publishArticle('article-area-inference');
        $article->areas()->attach($area);
        $this->publishProject('project-same-area', $area);

        $response = $this->get('/blog/article-area-inference');

        $response->assertOk();
        $response->assertSee('مشاريع مرتبطة بالموضوع');
        $response->assertSee('project-same-area');
        $response->assertDontSee('مشاريع المقال');
    }

    public function test_a_project_sharing_the_articles_service_appears_as_topically_related(): void
    {
        $service = Service::factory()->create();
        $article = $this->publishArticle('article-service-inference');
        $article->services()->attach($service);

        $project = $this->publishProject('project-same-service');
        $project->services()->attach($service);

        $response = $this->get('/blog/article-service-inference');

        $response->assertSee('project-same-service');
    }

    public function test_a_project_with_no_shared_service_or_area_is_never_shown_as_related(): void
    {
        $article = $this->publishArticle('article-no-overlap');
        $article->areas()->attach(Area::factory()->create());
        $this->publishProject('unrelated-project');

        $response = $this->get('/blog/article-no-overlap');

        $response->assertDontSee('unrelated-project');
    }

    public function test_the_section_is_hidden_entirely_when_the_article_has_no_service_or_area_relations(): void
    {
        $article = $this->publishArticle('article-with-no-topics');
        // No services/areas attached at all - nothing to infer from.
        $this->publishProject('irrelevant-project');

        $response = $this->get('/blog/article-with-no-topics');

        $response->assertOk();
        $response->assertDontSee('مشاريع مرتبطة بالموضوع');
    }

    public function test_an_unpublished_project_is_never_shown_even_if_topically_related(): void
    {
        $area = Area::factory()->create();
        $article = $this->publishArticle('article-area-for-unpublished');
        $article->areas()->attach($area);

        // A Project sharing the area but with no published Page.
        Project::factory()->create(['area_id' => $area->id, 'title' => 'draft-project-same-area']);

        $response = $this->get('/blog/article-area-for-unpublished');

        $response->assertDontSee('draft-project-same-area');
    }
}
