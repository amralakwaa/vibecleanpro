<?php

namespace Tests\Feature;

use App\Enums\AreaTier;
use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Area;
use App\Models\ContentBlock;
use App\Models\Page;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Promotion to Tier A is a deliberate, evidenced, attributed act - never
 * automatic, never on an empty district.
 */
class AreaPromotionTest extends TestCase
{
    use RefreshDatabase;

    private function verifiedProjectInArea(Area $area): Project
    {
        $project = Project::factory()->create([
            'area_id' => $area->id,
            'challenge' => 'تحدٍّ موثَّق',
            'location_status' => 'verified',
            'location_confidence' => 4,
            'location_evidence_type' => 'contract',
            'location_evidence_reference' => 'contract-1',
            'verified_at' => now(),
            'verified_by' => User::factory(),
        ]);
        $page = Page::factory()->create(['type' => PageType::Project, 'slug' => 'p-'.$area->id, 'status' => PageStatus::Draft]);
        $project->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'x']]);
        $page->update(['status' => PageStatus::Published]);

        return $project;
    }

    public function test_an_area_with_no_verified_project_cannot_be_promoted(): void
    {
        $area = Area::factory()->create(['tier' => AreaTier::B, 'slug' => 'empty-area']);

        $code = Artisan::call('areas:promote', ['slug' => 'empty-area', '--reason' => 'محاولة']);

        $this->assertSame(1, $code, 'promotion is refused without evidence');
        $this->assertSame(AreaTier::B, $area->fresh()->tier);
    }

    public function test_promotion_requires_a_reason(): void
    {
        $area = Area::factory()->create(['tier' => AreaTier::B, 'slug' => 'has-evidence']);
        $this->verifiedProjectInArea($area);

        $code = Artisan::call('areas:promote', ['slug' => 'has-evidence']);

        $this->assertSame(1, $code, 'promotion is refused without a reason');
        $this->assertSame(AreaTier::B, $area->fresh()->tier);
    }

    public function test_a_verified_area_is_promoted_with_attribution(): void
    {
        $user = User::factory()->create();
        $area = Area::factory()->create(['tier' => AreaTier::B, 'slug' => 'ready-area']);
        $this->verifiedProjectInArea($area);

        $code = Artisan::call('areas:promote', [
            'slug' => 'ready-area',
            '--reason' => 'ثلاثة مشاريع موثّقة بعقود',
            '--by' => $user->id,
        ]);

        $fresh = $area->fresh();
        $this->assertSame(0, $code);
        $this->assertSame(AreaTier::A, $fresh->tier);
        $this->assertNotNull($fresh->promoted_at);
        $this->assertSame($user->id, $fresh->promoted_by);
        $this->assertSame('ثلاثة مشاريع موثّقة بعقود', $fresh->promotion_reason);
    }

    public function test_readiness_command_scores_a_district_with_evidence(): void
    {
        $area = Area::factory()->create(['tier' => AreaTier::B, 'name' => 'حي-جاهز']);
        $this->verifiedProjectInArea($area);

        $code = Artisan::call('areas:local-seo-readiness', ['--ready' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $code);
        $this->assertStringContainsString('حي-جاهز', $output);
        $this->assertStringContainsString('/100', $output);
    }
}
