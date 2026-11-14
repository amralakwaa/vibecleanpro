<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Area;
use App\Models\AreaGroup;
use App\Models\ContentBlock;
use App\Models\Page;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A project's facts rail names its district and the side of Riyadh that
 * district belongs to. The side links only when its landing page is
 * published, the same rule the area page and /areas apply.
 */
class ProjectAreaGroupContextTest extends TestCase
{
    use RefreshDatabase;

    private function verifiedProjectIn(Area $area): void
    {
        $project = Project::factory()->create([
            'area_id' => $area->id,
            'location_status' => 'verified',
            'location_confidence' => 4,
            'location_evidence_type' => 'contract',
            'location_evidence_reference' => 'contract-1',
            'verified_at' => now(),
            'verified_by' => User::factory(),
        ]);
        $page = Page::factory()->create(['type' => PageType::Project, 'slug' => 'job-in-group', 'status' => PageStatus::Draft]);
        $project->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'نص.']]);
        $page->update(['status' => PageStatus::Published]);
    }

    public function test_the_side_of_riyadh_is_linked_when_its_landing_page_is_published(): void
    {
        $group = AreaGroup::factory()->create(['name' => 'جهة-اختبار', 'slug' => 'test-side']);
        $landing = Page::factory()->create(['type' => PageType::Landing, 'slug' => 'test-side', 'published_at' => now()->subDay()]);
        $landing->pageable()->associate($group);
        $landing->status = PageStatus::Published;
        $landing->saveQuietly();
        $this->verifiedProjectIn(Area::factory()->create(['area_group_id' => $group->id]));

        $html = $this->get('/projects/job-in-group')->assertOk()->getContent();

        $this->assertStringContainsString('جهة-اختبار', $html);
        $this->assertStringContainsString('href="/areas/test-side"', $html);
    }

    public function test_the_side_of_riyadh_is_plain_text_without_a_published_landing_page(): void
    {
        $group = AreaGroup::factory()->create(['name' => 'جهة-بلا-صفحة', 'slug' => 'no-page-side']);
        $this->verifiedProjectIn(Area::factory()->create(['area_group_id' => $group->id]));

        $html = $this->get('/projects/job-in-group')->assertOk()->getContent();

        $this->assertStringContainsString('جهة-بلا-صفحة', $html);
        $this->assertStringNotContainsString('href="/areas/no-page-side"', $html);
    }
}
