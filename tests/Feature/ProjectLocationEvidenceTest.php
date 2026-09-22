<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Area;
use App\Models\ContentBlock;
use App\Models\Page;
use App\Models\Project;
use App\Models\ProjectLocationHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * The location governance gate: a district only becomes a local signal
 * when it is both reviewed (status verified) and evidenced (confidence at
 * least 3). Everything below that is stored and managed, but never
 * emitted - not in the schema, not in the visible facts rail.
 */
class ProjectLocationEvidenceTest extends TestCase
{
    use RefreshDatabase;

    private function projectInArea(string $slug, Area $area, array $location): Project
    {
        $project = Project::factory()->create(array_merge(['area_id' => $area->id], $location));

        $page = Page::factory()->create([
            'type' => PageType::Project,
            'slug' => $slug,
            'status' => PageStatus::Draft,
        ]);
        $project->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'تفاصيل.']]);
        $page->update(['status' => PageStatus::Published]);

        return $project->fresh(['page']);
    }

    private function locationInSchema(string $slug): bool
    {
        $html = $this->get("/projects/{$slug}")->assertOk()->getContent();

        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);

        foreach ($matches[1] as $json) {
            if (str_contains($json, 'contentLocation')) {
                return true;
            }
        }

        return false;
    }

    public function test_a_project_with_no_evidence_never_appears_in_seo(): void
    {
        $area = Area::factory()->create(['name' => 'حي-بلا-دليل']);
        $this->projectInArea('no-evidence', $area, [
            'location_status' => 'draft',
            'location_confidence' => 0,
        ]);

        $this->assertFalse($this->locationInSchema('no-evidence'), 'draft/0 must not emit contentLocation');
    }

    public function test_an_internal_record_alone_does_not_appear_in_seo(): void
    {
        $area = Area::factory()->create(['name' => 'حي-سجل-داخلي']);

        // An internal record (confidence 1) can never reach Verified - the
        // save validation forbids it - so it sits at pending_review and
        // must not emit.
        $this->projectInArea('internal-only', $area, [
            'location_status' => 'pending_review',
            'location_confidence' => 1,
            'location_source' => 'internal_record',
        ]);

        $this->assertFalse($this->locationInSchema('internal-only'), 'confidence 1 (pending) must not emit');
    }

    public function test_client_provided_confidence_two_still_does_not_appear(): void
    {
        $area = Area::factory()->create(['name' => 'حي-افاد-العميل']);
        $this->projectInArea('client-two', $area, [
            'location_status' => 'pending_review',
            'location_confidence' => 2,
            'location_source' => 'client_provided',
        ]);

        $this->assertFalse($this->locationInSchema('client-two'), 'confidence 2 is below the SEO threshold of 3');
    }

    public function test_real_media_evidence_appears_in_seo(): void
    {
        $area = Area::factory()->create(['name' => 'حي-دليل-مصور']);
        $this->projectInArea('media-evidence', $area, [
            'location_status' => 'verified',
            'location_confidence' => 3,
            'location_source' => 'image_metadata',
            'location_evidence_type' => 'photo_metadata',
            'location_evidence_reference' => 'project-photo-01.webp',
            'verified_at' => now(),
            'verified_by' => User::factory(),
        ]);

        $this->assertTrue($this->locationInSchema('media-evidence'), 'verified + confidence 3 must emit contentLocation');
        $html = $this->get('/projects/media-evidence')->getContent();
        $this->assertStringContainsString('حي-دليل-مصور', $html, 'the verified district shows in the visible page');
    }

    public function test_rejected_location_never_appears_even_with_high_confidence(): void
    {
        $area = Area::factory()->create(['name' => 'حي-مرفوض']);
        $this->projectInArea('rejected', $area, [
            'location_status' => 'rejected',
            'location_confidence' => 4,
        ]);

        $this->assertFalse($this->locationInSchema('rejected'), 'a rejected claim is never emitted, whatever its confidence');
    }

    public function test_verified_status_cannot_be_saved_with_weak_confidence(): void
    {
        $area = Area::factory()->create();
        $project = $this->projectInArea('invalid', $area, ['location_status' => 'draft', 'location_confidence' => 0]);

        $this->expectException(ValidationException::class);

        // Verified needs confidence >= 3; the model refuses the combination.
        $project->update(['location_status' => 'verified', 'location_confidence' => 1, 'verified_at' => now(), 'verified_by' => User::factory()->create()->id]);
    }

    public function test_verified_requires_evidence_type_and_reference(): void
    {
        $area = Area::factory()->create();
        $project = $this->projectInArea('needs-evidence', $area, ['location_status' => 'draft', 'location_confidence' => 0]);

        $this->expectException(ValidationException::class);

        // Confidence is high enough, but no evidence type or reference -
        // verification must still be refused.
        $project->update(['location_status' => 'verified', 'location_confidence' => 4, 'verified_at' => now(), 'verified_by' => User::factory()->create()->id]);
    }

    public function test_verifying_stamps_who_and_when(): void
    {
        $area = Area::factory()->create();
        $project = $this->projectInArea('stamp', $area, ['location_status' => 'draft', 'location_confidence' => 0]);
        $this->assertNull($project->verified_at);

        $project->update([
            'location_status' => 'verified',
            'location_confidence' => 3,
            'location_evidence_type' => 'contract',
            'location_evidence_reference' => 'contract-123',
            'verified_at' => now(),
            'verified_by' => User::factory()->create()->id,
        ]);

        $this->assertNotNull($project->fresh()->verified_at, 'verified_at is stamped on verification');

        // Leaving Verified clears the stamp so it cannot go stale on a
        // downgraded claim.
        $project->update(['location_status' => 'pending_review', 'verified_at' => null, 'verified_by' => null]);
        $this->assertNull($project->fresh()->verified_at, 'the stamp clears when it leaves Verified');
    }

    public function test_an_evidence_type_change_is_recorded_in_history(): void
    {
        $area = Area::factory()->create();
        $project = $this->projectInArea('evidence-change', $area, ['location_status' => 'draft', 'location_confidence' => 0]);
        $baseline = ProjectLocationHistory::where('project_id', $project->id)->count();

        $project->update(['location_evidence_type' => 'invoice']);

        $this->assertSame($baseline + 1, ProjectLocationHistory::where('project_id', $project->id)->count());
    }

    public function test_a_source_change_is_recorded_in_history(): void
    {
        $area = Area::factory()->create();
        $project = $this->projectInArea('source-change', $area, ['location_status' => 'draft', 'location_confidence' => 0]);
        $baseline = ProjectLocationHistory::where('project_id', $project->id)->count();

        $project->update(['location_source' => 'client_provided']);

        $this->assertSame($baseline + 1, ProjectLocationHistory::where('project_id', $project->id)->count());
    }

    public function test_a_status_change_is_recorded_in_history(): void
    {
        $area = Area::factory()->create();
        $project = $this->projectInArea('history', $area, [
            'location_status' => 'draft',
            'location_confidence' => 0,
        ]);

        $baseline = ProjectLocationHistory::where('project_id', $project->id)->count();

        $project->update(['location_status' => 'pending_review']);
        $project->update([
            'location_status' => 'verified',
            'location_confidence' => 3,
            'location_evidence_type' => 'site_report',
            'location_evidence_reference' => 'report-2026',
            'verified_at' => now(),
            'verified_by' => User::factory()->create()->id,
        ]);

        $rows = ProjectLocationHistory::where('project_id', $project->id)->get();
        $this->assertSame($baseline + 2, $rows->count(), 'each status change writes one history row');

        $latest = $rows->sortByDesc('id')->first();
        $this->assertSame('verified', $latest->new_location['location_status']);
        $this->assertSame('pending_review', $latest->old_location['location_status']);
    }

    public function test_editing_a_note_does_not_create_history_noise(): void
    {
        $area = Area::factory()->create();
        $project = $this->projectInArea('note', $area, ['location_status' => 'draft', 'location_confidence' => 0]);
        $baseline = ProjectLocationHistory::where('project_id', $project->id)->count();

        $project->update(['location_note' => 'ملاحظة داخلية محدثة']);

        $this->assertSame($baseline, ProjectLocationHistory::where('project_id', $project->id)->count());
    }
}
