<?php

namespace Tests\Feature;

use App\Enums\LocationConfidence;
use App\Enums\LocationEvidenceType;
use App\Enums\LocationStatus;
use App\Models\Area;
use App\Models\Project;
use App\Models\User;
use App\Services\Seo\LocationEvidenceWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LocationEvidenceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private LocationEvidenceWorkflow $workflow;

    private int $actorId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workflow = app(LocationEvidenceWorkflow::class);
        $this->actorId = User::factory()->create()->id;
    }

    // ─────────────────────────────────────────────────────────
    // Helper
    // ─────────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function pendingProject(array $overrides = []): Project
    {
        return Project::factory()
            ->for(Area::factory())
            ->create(array_merge([
                'location_status' => LocationStatus::PendingReview,
                'location_confidence' => LocationConfidence::MediaEvidence,
                'location_evidence_type' => LocationEvidenceType::PhotoMetadata,
                'location_evidence_reference' => 'ref.jpg',
            ], $overrides));
    }

    // ─────────────────────────────────────────────────────────
    // APPROVE — happy path
    // ─────────────────────────────────────────────────────────

    /** @test */
    public function test_approve_valid_pending_review_transitions_to_verified(): void
    {
        $project = $this->pendingProject();

        $this->workflow->approve($project, $this->actorId);

        $this->assertEquals(LocationStatus::Verified, $project->fresh()->location_status);
    }

    /** @test */
    public function test_approve_populates_verified_by(): void
    {
        $project = $this->pendingProject();

        $this->workflow->approve($project, $this->actorId);

        $this->assertEquals($this->actorId, $project->fresh()->verified_by);
    }

    /** @test */
    public function test_approve_populates_verified_at(): void
    {
        $project = $this->pendingProject();

        $this->workflow->approve($project, $this->actorId);

        $this->assertNotNull($project->fresh()->verified_at);
    }

    /** @test */
    public function test_approve_creates_exactly_one_history_record(): void
    {
        $project = $this->pendingProject();
        $before = $project->locationHistory()->count();

        $this->workflow->approve($project, $this->actorId);

        $this->assertEquals($before + 1, $project->fresh()->locationHistory()->count());
    }

    // ─────────────────────────────────────────────────────────
    // APPROVE — guard failures
    // ─────────────────────────────────────────────────────────

    /** @test */
    public function test_approve_without_evidence_reference_throws(): void
    {
        $project = $this->pendingProject(['location_evidence_reference' => null]);

        $this->expectException(ValidationException::class);
        $this->workflow->approve($project, $this->actorId);
    }

    /** @test */
    public function test_approve_with_confidence_below_threshold_throws(): void
    {
        $project = $this->pendingProject(['location_confidence' => LocationConfidence::from(1)]);

        $this->expectException(ValidationException::class);
        $this->workflow->approve($project, $this->actorId);
    }

    /** @test */
    public function test_approve_without_location_throws(): void
    {
        $project = Project::factory()->create([
            'location_status' => LocationStatus::PendingReview,
            'location_confidence' => LocationConfidence::MediaEvidence,
            'location_evidence_type' => LocationEvidenceType::PhotoMetadata,
            'location_evidence_reference' => 'ref.jpg',
            'area_id' => null,
            'neighborhood' => null,
            'city' => null,
            'landmark' => null,
        ]);

        $this->expectException(ValidationException::class);
        $this->workflow->approve($project, $this->actorId);
    }

    /** @test */
    public function test_direct_verified_save_without_verified_by_throws(): void
    {
        $project = $this->pendingProject();

        $this->expectException(ValidationException::class);

        $project->location_status = LocationStatus::Verified;
        // verified_by and verified_at intentionally NOT set
        $project->save();
    }

    /** @test */
    public function test_direct_verified_save_with_low_confidence_throws(): void
    {
        $project = $this->pendingProject(['location_confidence' => LocationConfidence::from(1)]);

        $this->expectException(ValidationException::class);

        $project->location_status = LocationStatus::Verified;
        $project->verified_by = 1;
        $project->verified_at = now();
        $project->save();
    }

    // ─────────────────────────────────────────────────────────
    // REJECT
    // ─────────────────────────────────────────────────────────

    /** @test */
    public function test_reject_transitions_pending_review_to_rejected(): void
    {
        $project = $this->pendingProject();

        $this->workflow->reject($project, $this->actorId);

        $this->assertEquals(LocationStatus::Rejected, $project->fresh()->location_status);
    }

    /** @test */
    public function test_reject_clears_verification_stamps(): void
    {
        $project = $this->pendingProject(['verified_by' => $this->actorId, 'verified_at' => now()]);

        $this->workflow->reject($project, $this->actorId);

        $fresh = $project->fresh();
        $this->assertNull($fresh->verified_by);
        $this->assertNull($fresh->verified_at);
    }

    /** @test */
    public function test_reject_does_not_delete_evidence(): void
    {
        $project = $this->pendingProject();

        $this->workflow->reject($project, $this->actorId);

        $fresh = $project->fresh();
        $this->assertEquals(LocationEvidenceType::PhotoMetadata, $fresh->location_evidence_type);
        $this->assertEquals('ref.jpg', $fresh->location_evidence_reference);
        $this->assertNotNull($fresh->area_id);
    }

    /** @test */
    public function test_reject_does_not_set_verified_status(): void
    {
        $project = $this->pendingProject();

        $this->workflow->reject($project, $this->actorId);

        $this->assertNotEquals(LocationStatus::Verified, $project->fresh()->location_status);
    }

    // ─────────────────────────────────────────────────────────
    // RETURN TO REVIEW
    // ─────────────────────────────────────────────────────────

    /** @test */
    public function test_return_to_review_transitions_verified_to_pending_review(): void
    {
        $project = Project::factory()
            ->for(Area::factory())
            ->create([
                'location_status' => LocationStatus::Verified,
                'location_confidence' => LocationConfidence::MediaEvidence,
                'location_evidence_type' => LocationEvidenceType::PhotoMetadata,
                'location_evidence_reference' => 'ref.jpg',
                'verified_by' => $this->actorId,
                'verified_at' => now(),
            ]);

        $this->workflow->returnToReview($project, $this->actorId);

        $this->assertEquals(LocationStatus::PendingReview, $project->fresh()->location_status);
    }

    /** @test */
    public function test_return_to_review_clears_verification_stamps(): void
    {
        $project = Project::factory()
            ->for(Area::factory())
            ->create([
                'location_status' => LocationStatus::Verified,
                'location_confidence' => LocationConfidence::MediaEvidence,
                'location_evidence_type' => LocationEvidenceType::PhotoMetadata,
                'location_evidence_reference' => 'ref.jpg',
                'verified_by' => $this->actorId,
                'verified_at' => now(),
            ]);

        $this->workflow->returnToReview($project, $this->actorId);

        $fresh = $project->fresh();
        $this->assertNull($fresh->verified_by);
        $this->assertNull($fresh->verified_at);
    }

    // ─────────────────────────────────────────────────────────
    // HISTORY
    // ─────────────────────────────────────────────────────────

    /** @test */
    public function test_ordinary_location_field_edit_still_creates_history(): void
    {
        $project = Project::factory()
            ->for(Area::factory())
            ->create(['location_status' => LocationStatus::Draft]);

        $before = $project->locationHistory()->count();

        $project->neighborhood = 'لبن';
        $project->save();

        $this->assertEquals($before + 1, $project->fresh()->locationHistory()->count());
    }

    /** @test */
    public function test_location_note_only_change_does_not_create_history(): void
    {
        $project = Project::factory()->create(['location_status' => LocationStatus::Draft]);
        $before = $project->locationHistory()->count();

        $project->location_note = 'ملاحظة';
        $project->save();

        $this->assertEquals($before, $project->fresh()->locationHistory()->count());
    }

    /** @test */
    public function test_noop_location_save_does_not_create_history(): void
    {
        $project = Project::factory()->create(['location_status' => LocationStatus::Draft]);
        $project->locationHistory()->count(); // ensure we have baseline
        $before = $project->locationHistory()->count();

        $project->title = 'عنوان جديد';
        $project->save();

        $this->assertEquals($before, $project->fresh()->locationHistory()->count());
    }

    /** @test */
    public function test_history_actor_is_recorded_when_workflow_has_actor(): void
    {
        $project = $this->pendingProject();

        $this->workflow->approve($project, $this->actorId);

        $this->assertEquals($this->actorId, $project->locationHistory()->first()->changed_by);
    }

    // ─────────────────────────────────────────────────────────
    // CLI / programmatic safeguards
    // ─────────────────────────────────────────────────────────

    /** @test */
    public function test_approve_from_wrong_status_throws(): void
    {
        $project = $this->pendingProject(['location_status' => LocationStatus::Draft]);

        $this->expectException(ValidationException::class);
        $this->workflow->approve($project, $this->actorId);
    }
}
