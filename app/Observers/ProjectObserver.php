<?php

namespace App\Observers;

use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ProjectObserver
{
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
    }
}
