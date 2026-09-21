<?php

namespace App\Enums;

/**
 * The review state of a project's location claim.
 *
 * A district is not either "trusted" or "not" - it moves through review.
 * draft: entered, not looked at. pending_review: waiting on a checker.
 * verified: signed off, may power SEO (with enough confidence). rejected:
 * looked at and refused, and it stays refused rather than silently
 * reverting to draft, so a bad claim cannot creep back in.
 *
 * Only `verified` is ever a green light, and even then confidence has the
 * final say - see Project::hasVerifiedLocation().
 */
enum LocationStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Verified = 'verified';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'مسودة',
            self::PendingReview => 'قيد المراجعة',
            self::Verified => 'موثَّق',
            self::Rejected => 'مرفوض',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::PendingReview => 'warning',
            self::Verified => 'success',
            self::Rejected => 'danger',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
