<?php

namespace App\Observers;

use App\Models\Testimonial;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TestimonialObserver
{
    /**
     * No review goes public without a known source, the customer's consent
     * and an approver. Any edit to the quoted text withdraws the approval,
     * so an approved review cannot be quietly rewritten.
     */
    public function saving(Testimonial $testimonial): void
    {
        if ($testimonial->exists && $testimonial->approved_at && $testimonial->isDirty(['content', 'author_name', 'rating']) && ! $testimonial->isDirty('approved_at')) {
            $testimonial->approved_at = null;
            $testimonial->approved_by = null;
        }

        if (! $testimonial->approved_at || ! $testimonial->isDirty('approved_at')) {
            return;
        }

        $user = Auth::user();

        if ($user && ! $user->can('approve_testimonial')) {
            throw ValidationException::withMessages(['approved_at' => 'اعتماد آراء العملاء من صلاحية الإدارة.']);
        }

        if (blank($testimonial->source) || ! $testimonial->consent_confirmed) {
            throw ValidationException::withMessages(['approved_at' => 'لا يُعتمد رأي قبل تحديد مصدره وتأكيد موافقة العميل على نشره.']);
        }

        $testimonial->approved_by ??= $user?->id;
    }
}
