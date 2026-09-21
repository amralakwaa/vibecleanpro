<?php

namespace App\Observers;

use App\Enums\LocationStatus;
use App\Models\Project;
use App\Models\ProjectLocationHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ProjectObserver
{
    /**
     * The fields whose change is worth an audit row: the place itself, and
     * the governance around it - source, status and confidence. A location note
     * is descriptive, not a claim, so a typo fix in it is deliberately
     * not logged.
     *
     * @var list<string>
     */
    private const LOCATION_FIELDS = ['area_id', 'city', 'neighborhood', 'landmark', 'location_source', 'location_evidence_type', 'location_status', 'location_confidence'];

    /**
     * Confirming that a project really happened is the owner's call
     * (confirm_project), not an editor's - the Publishing Gate trusts it.
     */
    public function saving(Project $project): void
    {
        $user = Auth::user();

        if ($user && $project->isDirty('owner_confirmed_at') && $project->owner_confirmed_at !== null && ! $user->can('confirm_project')) {
            throw ValidationException::withMessages(['owner_confirmed_at' => 'تأكيد المشروع من صلاحية المالك أو الإدارة.']);
        }

        // A location may be marked Verified only on real, referenced
        // evidence. The read gate would refuse to emit anything weaker
        // anyway, but allowing the state to be saved invites "why isn't my
        // verified district showing?" - so the whole contract is enforced
        // at the point of save, with a specific message per missing part.
        if ($project->location_status === LocationStatus::Verified) {
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

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }
        }

        // Stamp who verified it and when, the moment status becomes
        // Verified - and clear the stamp if it ever leaves that state, so
        // a stale signature cannot linger on a downgraded claim.
        if ($project->isDirty('location_status')) {
            if ($project->location_status === LocationStatus::Verified) {
                $project->verified_at = $project->verified_at ?? now();
                $project->verified_by = $project->verified_by ?? Auth::id();
            } else {
                $project->verified_at = null;
                $project->verified_by = null;
            }
        }
    }

    /**
     * Record a location change once it is committed. `getOriginal()` still
     * holds the pre-save values inside the saved event (Laravel syncs the
     * original only after this fires), so old vs new is exact - and a row
     * is written only when a real place field actually moved.
     */
    public function saved(Project $project): void
    {
        $moved = collect(self::LOCATION_FIELDS)->contains(fn (string $field) => $project->wasChanged($field));

        if (! $moved) {
            return;
        }

        $old = [
            'area_id' => $project->getOriginal('area_id'),
            'city' => $project->getOriginal('city'),
            'neighborhood' => $project->getOriginal('neighborhood'),
            'landmark' => $project->getOriginal('landmark'),
            'location_note' => $project->getOriginal('location_note'),
            'location_source' => $project->getOriginal('location_source'),
            'location_evidence_type' => $project->getOriginal('location_evidence_type'),
            'location_confidence' => $project->getOriginal('location_confidence'),
            'location_status' => $project->getOriginal('location_status'),
            'location_evidence_reference' => $project->getOriginal('location_evidence_reference'),
        ];

        ProjectLocationHistory::create([
            'project_id' => $project->id,
            'old_location' => $old,
            'new_location' => $project->locationSnapshot(),
            'source' => $project->location_source?->value,
            'changed_by' => Auth::id(),
            'created_at' => now(),
        ]);
    }
}
