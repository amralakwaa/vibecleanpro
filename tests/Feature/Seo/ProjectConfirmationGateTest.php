<?php

namespace Tests\Feature\Seo;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\ContentBlock;
use App\Models\Page;
use App\Models\Project;
use App\Seo\PublishingGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectConfirmationGateTest extends TestCase
{
    use RefreshDatabase;

    private function projectPage(Project $project): Page
    {
        $page = Page::factory()->create(['type' => PageType::Project, 'title' => 'تنظيف فيلا — الرياض', 'slug' => 'villa-cleaning-villa-01', 'status' => PageStatus::Draft]);
        $project->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => '<p>وصف</p>'], 'position' => 1]);

        return $page->fresh();
    }

    public function test_an_unconfirmed_library_candidate_cannot_be_published(): void
    {
        $page = $this->projectPage(Project::factory()->unconfirmed()->create(['source_ref' => 'CAND-20260614-VILLA-01']));

        $page->update(['status' => PageStatus::Published]);

        $this->assertSame(PageStatus::Draft, $page->fresh()->status);
        $check = collect(app(PublishingGate::class)->evaluate($page->fresh())->checks)->firstWhere('key', 'project_confirmed');
        $this->assertSame('error', $check->severity->value);
    }

    public function test_an_owner_confirmed_project_passes_the_check(): void
    {
        $page = $this->projectPage(Project::factory()->create());

        $check = collect(app(PublishingGate::class)->evaluate($page)->checks)->firstWhere('key', 'project_confirmed');
        $this->assertSame('pass', $check->severity->value);
    }
}
