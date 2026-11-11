<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Enums\ProjectCluster;
use App\Models\Area;
use App\Models\Article;
use App\Models\ContentBlock;
use App\Models\Page;
use App\Models\Project;
use App\Models\SeoMetadata;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Related-content picks used to fall back to id order, so the oldest
 * article and the first projects of a cluster took a link from every page
 * while newer ones stayed near-orphaned. These pin the relevance order.
 */
class RelatedContentOrderingTest extends TestCase
{
    use RefreshDatabase;

    private function publish(Article|Project|Service $model, PageType $type, string $slug): void
    {
        $page = Page::factory()->create(['type' => $type, 'slug' => $slug, 'title' => $slug]);
        $model->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'محتوى.']]);
        SeoMetadata::factory()->for($page)->create();
        $page->update(['status' => PageStatus::Published]);
    }

    private function article(string $slug): Article
    {
        $article = Article::factory()->create(['title' => $slug]);
        $this->publish($article, PageType::Article, $slug);

        return $article;
    }

    private function service(string $slug): Service
    {
        $service = Service::factory()->create();
        $this->publish($service, PageType::Service, $slug);

        return $service;
    }

    private function project(string $slug, ?ProjectCluster $cluster = null): Project
    {
        $project = Project::factory()->create(['title' => $slug, 'cluster' => $cluster]);
        $this->publish($project, PageType::Project, $slug);

        return $project;
    }

    public function test_an_article_shows_the_articles_sharing_its_services_before_older_generic_ones(): void
    {
        $tanks = $this->service('tanks');
        $others = collect(range(1, 4))->map(fn (int $i) => $this->service("other-{$i}"));

        // Created first, so id order alone would put them on every page.
        $generic = $this->article('generic-guide');
        $generic->services()->attach($others->pluck('id'));
        $this->article('unrelated-old');
        $this->article('unrelated-older');

        $current = $this->article('tank-how-often');
        $current->services()->attach($tanks);
        $sibling = $this->article('tank-steps');
        $sibling->services()->attach($tanks);

        $related = $this->get('/blog/tank-how-often')->assertOk()->viewData('relatedArticles');

        $this->assertSame('tank-steps', $related->first()->page->slug);
        $this->assertCount(3, $related);
    }

    public function test_a_service_page_lists_its_specific_articles_before_site_wide_guides(): void
    {
        $tanks = $this->service('tanks');
        $others = collect(range(1, 3))->map(fn (int $i) => $this->service("other-{$i}"));

        $guide = $this->article('site-wide-guide');
        $guide->services()->attach($others->pluck('id')->push($tanks->id));
        $specific = $this->article('tank-specific');
        $specific->services()->attach($tanks);

        $articles = $this->get('/services/tanks')->assertOk()->viewData('articles');

        $this->assertSame(['tank-specific', 'site-wide-guide'], $articles->map(fn (Article $a) => $a->page->slug)->all());
    }

    public function test_a_verified_project_alone_in_its_district_is_filled_with_its_own_kind_of_work(): void
    {
        $area = Area::factory()->create();
        $lonely = $this->project('district-only', ProjectCluster::Facade);
        $lonely->forceFill([
            'area_id' => $area->id,
            'location_status' => 'verified',
            'location_confidence' => 3,
            'location_evidence_type' => 'manual_record',
            'location_evidence_reference' => 'owner-confirmation',
            'verified_at' => now(),
            'verified_by' => User::factory()->create()->id,
        ])->save();
        $this->project('facade-elsewhere', ProjectCluster::Facade);
        $this->project('villa-elsewhere', ProjectCluster::Villa);

        $response = $this->get('/projects/district-only')->assertOk();

        $this->assertSame(['facade-elsewhere'], $response->viewData('relatedProjects')->map(fn (Project $p) => $p->page->slug)->all());
        $this->assertSame('مشاريع أخرى من النوع نفسه', $response->viewData('relatedProjectsLabel'));
    }

    public function test_projects_in_a_cluster_link_to_their_next_neighbours_and_wrap_round(): void
    {
        $projects = collect(range(1, 5))->map(fn (int $i) => $this->project("facade-{$i}", ProjectCluster::Facade));
        $this->project('villa-x', ProjectCluster::Villa);

        $slugsFor = fn (string $slug) => $this->get("/projects/{$slug}")->assertOk()
            ->viewData('relatedProjects')->map(fn (Project $p) => $p->page->slug)->all();

        $this->assertSame(['facade-2', 'facade-3', 'facade-4'], $slugsFor('facade-1'));
        $this->assertSame(['facade-5', 'facade-1', 'facade-2'], $slugsFor('facade-4'));

        // Every project in the cluster now receives links, not just the first three.
        $linked = $projects->flatMap(fn (Project $p) => $slugsFor($p->page->slug))->unique()->sort()->values()->all();
        $this->assertSame(['facade-1', 'facade-2', 'facade-3', 'facade-4', 'facade-5'], $linked);
    }
}
