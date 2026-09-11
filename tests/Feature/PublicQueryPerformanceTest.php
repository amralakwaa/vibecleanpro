<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Area;
use App\Models\ContentBlock;
use App\Models\Media;
use App\Models\Page;
use App\Models\Project;
use App\Models\SeoMetadata;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A coarse N+1 guard: a listing of a realistic number of rows must stay
 * under a fixed query budget rather than scaling per row (see the Phase 6
 * report's Performance section). This is a regression tripwire, not a
 * full query audit.
 */
class PublicQueryPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private function publishService(string $slug): void
    {
        $media = Media::factory()->create();
        $service = Service::factory()->create(['featured_media_id' => $media->id]);
        $page = Page::factory()->create(['type' => PageType::Service, 'slug' => $slug]);
        $service->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'نص.']]);
        SeoMetadata::factory()->for($page)->create();
        $page->update(['status' => PageStatus::Published]);
    }

    public function test_the_services_index_stays_under_a_fixed_query_budget_for_a_dozen_rows(): void
    {
        for ($i = 0; $i < 12; $i++) {
            $this->publishService("perf-service-{$i}");
        }

        $queryCount = 0;
        DB::listen(function () use (&$queryCount) {
            $queryCount++;
        });

        $this->get('/services')->assertOk();

        // A hand-rolled per-card query (e.g. resolving each Service's
        // featured image or URL individually) would blow well past this
        // with 12 rows; a properly eager-loaded index stays flat.
        $this->assertLessThan(20, $queryCount, "Services index ran {$queryCount} queries for 12 rows - investigate for an N+1.");
    }

    public function test_the_home_page_stays_under_a_fixed_query_budget_with_a_realistic_content_volume(): void
    {
        $area = Area::factory()->create();
        $areaPage = Page::factory()->create(['type' => PageType::Area, 'slug' => 'perf-area']);
        $area->page()->save($areaPage);
        ContentBlock::factory()->for($areaPage)->create(['type' => 'rich_text', 'data' => ['content' => 'نص.']]);
        $areaPage->update(['status' => PageStatus::Published]);

        for ($i = 0; $i < 6; $i++) {
            $this->publishService("perf-home-service-{$i}");
            $project = Project::factory()->create(['area_id' => $area->id]);
            $projectPage = Page::factory()->create(['type' => PageType::Project, 'slug' => "perf-project-{$i}"]);
            $project->page()->save($projectPage);
            ContentBlock::factory()->for($projectPage)->create(['type' => 'rich_text', 'data' => ['content' => 'نص.']]);
            $projectPage->update(['status' => PageStatus::Published]);
        }

        $queryCount = 0;
        DB::listen(function () use (&$queryCount) {
            $queryCount++;
        });

        $this->get('/')->assertOk();

        $this->assertLessThan(60, $queryCount, "Home page ran {$queryCount} queries - investigate before this grows further.");
    }
}
