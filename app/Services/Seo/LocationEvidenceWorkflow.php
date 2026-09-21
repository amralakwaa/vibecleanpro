<?php

namespace App\Services\Seo;

use App\Enums\LocationStatus;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LocationEvidenceWorkflow
{
    /**
     * Approve the location evidence.
     * Transitions from pending_review -> verified.
     */
    public function approve(Project $project, ?int $actorId = null): void
    {
        if ($project->location_status !== LocationStatus::PendingReview) {
            throw ValidationException::withMessages(['location_status' => 'يجب أن يكون المشروع في حالة المراجعة للاعتماد.']);
        }

        $errors = [];

        if ($project->location_confidence === null || ! $project->location_confidence->meetsSeoThreshold()) {
            $errors['location_confidence'] = 'الاعتماد يتطلب مستوى ثقة 3 (دليل مصوَّر) فأعلى.';
        }

        if ($project->location_evidence_type === null) {
            $errors['location_evidence_type'] = 'الاعتماد يتطلب تحديد نوع الدليل.';
        }

        if (blank($project->location_evidence_reference)) {
            $errors['location_evidence_reference'] = 'الاعتماد يتطلب مرجع الإثبات (اسم ملف أو مستند).';
        }

        if (! $project->area_id && blank($project->neighborhood) && blank($project->city) && blank($project->landmark)) {
            $errors['area_id'] = 'الاعتماد يتطلب تحديد موقع (حي أو مدينة أو معلم).';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        DB::transaction(function () use ($project, $actorId) {
            $project->location_status = LocationStatus::Verified;
            $project->verified_by = $actorId;
            $project->verified_at = now();
            $project->location_changed_by = $actorId;

            $project->save();
        });
    }

    /**
     * Reject the location evidence.
     * Transitions from pending_review -> rejected.
     */
    public function reject(Project $project, ?int $actorId = null): void
    {
        if ($project->location_status !== LocationStatus::PendingReview) {
            throw ValidationException::withMessages(['location_status' => 'يجب أن يكون المشروع في حالة المراجعة للرفض.']);
        }

        DB::transaction(function () use ($project, $actorId) {
            $project->location_status = LocationStatus::Rejected;
            $project->verified_by = null;
            $project->verified_at = null;
            $project->location_changed_by = $actorId;

            $project->save();
        });
    }

    /**
     * Return to review.
     * Transitions from verified or rejected -> pending_review.
     */
    public function returnToReview(Project $project, ?int $actorId = null): void
    {
        if (! in_array($project->location_status, [LocationStatus::Verified, LocationStatus::Rejected], true)) {
            throw ValidationException::withMessages(['location_status' => 'يجب أن يكون المشروع معتمداً أو مرفوضاً للإعادة للمراجعة.']);
        }

        DB::transaction(function () use ($project, $actorId) {
            $project->location_status = LocationStatus::PendingReview;
            $project->verified_by = null;
            $project->verified_at = null;
            $project->location_changed_by = $actorId;

            $project->save();
        });
    }
}
