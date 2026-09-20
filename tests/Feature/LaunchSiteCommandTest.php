<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Enums\ServiceCapability;
use App\Models\Article;
use App\Models\ContentBlock;
use App\Models\Media;
use App\Models\Page;
use App\Models\Project;
use App\Models\SeoMetadata;
use App\Support\Launch\LaunchManifest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

class LaunchSiteCommandTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    private string $manifestPath;

    private Page $service;

    private Page $article;

    private Page $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manifestPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'launch-'.uniqid().'.json';
        $this->service = $this->createCompliantServicePage('villa-cleaning', PageStatus::Draft);
        $this->article = $this->draftArticle('villa-cleaning-checklist', '<p>اقرأ <a href="/services/villa-cleaning">تنظيف الفلل</a>.</p>');
        $this->project = $this->draftProject('CAND-TEST-01');
    }

    protected function tearDown(): void
    {
        @unlink($this->manifestPath);

        parent::tearDown();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function writeManifest(array $overrides = []): string
    {
        file_put_contents($this->manifestPath, json_encode(array_replace([
            'version' => 1,
            'confirm_projects' => ['source_refs' => ['CAND-TEST-01']],
            'article_services' => ['villa-cleaning-checklist' => ['villa-cleaning']],
            'publish' => [
                'services' => ['villa-cleaning'],
                'articles' => ['villa-cleaning-checklist'],
                'projects' => ['CAND-TEST-01'],
            ],
            'homepage_hero_media_file' => null,
        ], $overrides)));

        return $this->manifestPath;
    }

    private function draftArticle(string $slug, string $html): Page
    {
        $article = Article::factory()->create(['featured_media_id' => Media::factory()->create()->id]);
        $page = Page::factory()->create(['type' => PageType::Article, 'title' => 'قائمة تنظيف الفيلا', 'slug' => $slug, 'status' => PageStatus::Draft]);
        $article->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => $html]]);
        ContentBlock::factory()->for($page)->create(['type' => 'cta', 'data' => ['heading' => 'اطلب', 'button_label' => 'اطلب', 'button_url' => url('/quote')]]);
        SeoMetadata::factory()->for($page)->create(['meta_title' => 'قائمة تنظيف الفيلا', 'meta_description' => 'ما يشمله تنظيف الفيلا.']);

        return $page->fresh();
    }

    private function draftProject(string $ref): Page
    {
        $project = Project::factory()->unconfirmed()->create(['source_ref' => $ref, 'title' => 'تنظيف فيلا — الرياض']);
        $project->media()->attach(Media::factory()->create()->id, ['stage' => 'after', 'sort_order' => 1]);
        $page = Page::factory()->create(['type' => PageType::Project, 'title' => 'تنظيف فيلا — الرياض', 'slug' => 'villa-cleaning-test-01', 'status' => PageStatus::Draft]);
        $project->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => '<p>أعمال نفّذها الفريق.</p>']]);
        SeoMetadata::factory()->for($page)->create(['meta_title' => 'تنظيف فيلا', 'meta_description' => 'أعمال نفّذها الفريق.', 'robots_index' => false]);

        return $page->fresh();
    }

    private function launch(string $mode, ?string $manifest = null): int
    {
        return Artisan::call('site:launch', ['--mode' => $mode, '--manifest' => $manifest ?? $this->writeManifest()]);
    }

    public function test_the_shipped_manifest_records_the_approved_decisions(): void
    {
        $manifest = LaunchManifest::load();

        $this->assertCount(47, $manifest->confirmProjectRefs);
        // Wave 1 published 10 service pages and Wave 2 the remaining
        // eight, so the whole catalogue is live; the trust and legal
        // pages publish through the same manifest as standalone pages.
        $this->assertCount(18, $manifest->publishServices);
        $this->assertSame(['warranty', 'terms', 'privacy', 'about'], $manifest->publishPages);
        $this->assertCount(10, $manifest->publishArticles);
        $this->assertCount(7, $manifest->publishProjectRefs);
        $this->assertCount(10, $manifest->articleServices);
        $this->assertEmpty(array_diff($manifest->publishProjectRefs, $manifest->confirmProjectRefs));
        $this->assertNull($manifest->homepageHeroMediaFile);
    }

    public function test_a_dry_run_shows_the_plan_and_writes_nothing(): void
    {
        $this->assertSame(0, $this->launch('dry-run'));

        $this->assertStringContainsString('5 change(s) would be applied', Artisan::output());
        $this->assertSame(PageStatus::Draft, $this->service->fresh()->status);
        $this->assertNull($this->project->pageable->fresh()->owner_confirmed_at);
        $this->assertSame(0, $this->article->pageable->services()->count());
    }

    public function test_repeated_dry_runs_print_the_same_plan(): void
    {
        $this->launch('dry-run');
        $first = Artisan::output();
        $this->launch('dry-run');

        $this->assertSame($first, Artisan::output());
    }

    public function test_apply_confirms_links_and_publishes_through_the_gate(): void
    {
        $this->assertSame(0, $this->launch('apply'));

        $this->assertSame(PageStatus::Published, $this->service->fresh()->status);
        $this->assertSame(PageStatus::Published, $this->article->fresh()->status);
        $this->assertSame(PageStatus::Published, $this->project->fresh()->status);
        $this->assertNotNull($this->project->pageable->fresh()->owner_confirmed_at);
        $this->assertSame([$this->service->pageable_id], $this->article->pageable->services()->pluck('services.id')->all());
    }

    public function test_applying_twice_changes_nothing_the_second_time(): void
    {
        $this->launch('apply');
        $publishedAt = $this->service->fresh()->published_at;
        $confirmedAt = $this->project->pageable->fresh()->owner_confirmed_at;

        $this->travel(2)->hours();
        $this->assertSame(0, $this->launch('apply'));

        $this->assertStringContainsString('Applied: 0 change(s)', Artisan::output());
        $this->assertEquals($publishedAt, $this->service->fresh()->published_at);
        $this->assertEquals($confirmedAt, $this->project->pageable->fresh()->owner_confirmed_at);
        $this->assertSame(1, $this->article->pageable->services()->count());
    }

    public function test_a_gate_failure_refuses_the_whole_launch_and_writes_nothing(): void
    {
        $this->service->pageable->update(['capability_status' => ServiceCapability::NeedsConfirmation]);

        $this->assertSame(1, $this->launch('apply'));

        $this->assertStringContainsString('Publishing Gate refuses service villa-cleaning', Artisan::output());
        $this->assertSame(PageStatus::Draft, $this->article->fresh()->status);
        $this->assertNull($this->project->pageable->fresh()->owner_confirmed_at);
        $this->assertSame(0, $this->article->pageable->services()->count());
    }

    public function test_a_missing_dependency_fails_safely(): void
    {
        $manifest = $this->writeManifest(['publish' => ['services' => ['villa-cleaning'], 'articles' => ['villa-cleaning-checklist', 'not-seeded-yet'], 'projects' => ['CAND-TEST-01']]]);

        $this->assertSame(1, $this->launch('validate', $manifest));
        $this->assertStringContainsString('article page not-seeded-yet is not in the database', Artisan::output());
        $this->assertSame(1, $this->launch('apply', $manifest));
        $this->assertSame(PageStatus::Draft, $this->service->fresh()->status);
    }

    public function test_a_page_linking_to_an_unpublished_page_outside_the_launch_is_refused(): void
    {
        $this->createCompliantServicePage('sofa-cleaning', PageStatus::Draft);
        $this->article->contentBlocks()->where('type', 'rich_text')->first()->update(['data' => ['content' => '<p><a href="/services/sofa-cleaning">الكنب</a></p>']]);

        $this->assertSame(1, $this->launch('validate'));
        $this->assertStringContainsString('links to /services/sofa-cleaning', Artisan::output());
    }

    public function test_a_project_cannot_be_published_without_being_in_the_confirmation_list(): void
    {
        $manifest = $this->writeManifest(['confirm_projects' => ['source_refs' => []]]);

        $this->assertSame(1, $this->launch('validate', $manifest));
        $this->assertStringContainsString('not for owner confirmation', Artisan::output());
    }

    public function test_a_malformed_manifest_is_rejected_before_anything_runs(): void
    {
        file_put_contents($this->manifestPath, '{"version": 1, "publish": {"services": "villa-cleaning"}}');

        $this->assertSame(1, $this->launch('apply', $this->manifestPath));
        $this->assertStringContainsString('must be a list', Artisan::output());
        $this->assertSame(PageStatus::Draft, $this->service->fresh()->status);
    }
}
