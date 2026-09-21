<?php

namespace App\Console\Commands;

use App\Enums\AreaTier;
use App\Models\Area;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Promotes one district to Tier A - the deliberate, accountable step that
 * ReevaluateAreaTiers refuses to take on its own.
 *
 * A district may only be promoted when it actually has SEO-ready
 * evidence: at least one project there is verified with confidence >= 3
 * and a referenced artefact. Without that, promotion is refused - this is
 * the wall against doorway pages, enforced in code, not in policy.
 *
 * Every promotion records who did it, when, and why. There is no
 * auto-promotion anywhere in the system; a Tier A page is a claim to
 * Google, and a claim needs an author.
 */
class PromoteAreaToTierA extends Command
{
    protected $signature = 'areas:promote {slug : The area slug to promote} {--reason= : Why it is being promoted} {--by= : The promoting user id or email}';

    protected $description = 'Promote a district to Tier A, only when it has verified evidence, recording who and why';

    public function handle(): int
    {
        $area = Area::query()->where('slug', $this->argument('slug'))->first();

        if (! $area) {
            $this->error("No area with slug '{$this->argument('slug')}'.");

            return self::FAILURE;
        }

        if ($area->tier === AreaTier::A) {
            $this->warn("{$area->name} is already Tier A.");

            return self::SUCCESS;
        }

        $verified = $area->projects()
            ->where('location_status', 'verified')
            ->where('location_confidence', '>=', 3)
            ->whereNotNull('location_evidence_type')
            ->whereNotNull('location_evidence_reference')
            ->count();

        if ($verified === 0) {
            $this->error("{$area->name} has no SEO-ready project (verified, confidence >= 3, evidenced). Promotion refused - this is how doorway pages are prevented.");

            return self::FAILURE;
        }

        $reason = $this->option('reason');

        if (blank($reason)) {
            $this->error('A promotion reason is required (--reason). A Tier A page is a claim; it needs a stated basis.');

            return self::FAILURE;
        }

        $user = $this->resolveUser($this->option('by'));

        $area->forceFill([
            'tier' => AreaTier::A,
            'promoted_at' => now(),
            'promotion_reason' => $reason,
            'promoted_by' => $user?->id,
        ])->save();

        $this->info("{$area->name} promoted to Tier A ({$verified} verified project(s)).");
        $this->line('  By: '.($user?->name ?? 'unspecified').' · '.now()->toDateString());
        $this->comment('The area now MAY have an indexable page - building it is a separate, later step.');

        return self::SUCCESS;
    }

    private function resolveUser(?string $by): ?User
    {
        if (blank($by)) {
            return null;
        }

        return is_numeric($by)
            ? User::find($by)
            : User::where('email', $by)->first();
    }
}
