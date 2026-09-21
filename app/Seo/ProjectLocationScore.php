<?php

namespace App\Seo;

use App\Enums\LocationStatus;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

/**
 * How strong is the location evidence behind a single project, 0-100.
 *
 * This is the number the intelligence layer reasons with. It is computed
 * live from what the project actually has - never stored, never guessed -
 * so it can never drift from the data. It reads; it does not write.
 *
 * The six factors mirror the governance model: a clear district, a
 * source, a referenced document and a human sign-off are the location
 * itself (20 each); a linked service and a written case study are what
 * make that location worth a page (10 each). Reaching "verified" as a
 * status is not a factor on its own - a status without evidence would
 * score low, which is exactly the point.
 */
class ProjectLocationScore
{
    public const READY_THRESHOLD = 70;

    public const REVIEW_THRESHOLD = 40;

    /**
     * @return array{score: int, band: string, breakdown: array<string, int>}
     */
    public function for(Project $project): array
    {
        $breakdown = [
            'district' => ($project->area_id !== null || filled($project->neighborhood)) ? 20 : 0,
            'source' => $project->location_source !== null ? 20 : 0,
            'evidence' => ($project->location_evidence_type !== null && filled($project->location_evidence_reference)) ? 20 : 0,
            'human_approval' => ($project->location_status === LocationStatus::Verified && $project->verified_by !== null) ? 20 : 0,
            'service' => $this->hasService($project) ? 10 : 0,
            'case_study' => $project->hasCaseStudy() ? 10 : 0,
        ];

        $score = array_sum($breakdown);

        return [
            'score' => $score,
            'band' => $this->band($score),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * The SEO eligibility a page would actually key on - stricter than the
     * score, because a page needs the hard gate, not just points. This is
     * the same contract as Project::hasVerifiedLocation(), named for the
     * intelligence reports.
     */
    public function eligibility(Project $project): string
    {
        if ($project->hasVerifiedLocation()) {
            return 'VERIFIED';
        }

        $hasAnyEvidence = $project->area_id !== null
            || filled($project->neighborhood)
            || $project->location_source !== null
            || $project->location_evidence_type !== null;

        return $hasAnyEvidence ? 'REVIEW REQUIRED' : 'UNKNOWN';
    }

    private function band(int $score): string
    {
        return match (true) {
            $score >= self::READY_THRESHOLD => 'READY CANDIDATE',
            $score >= self::REVIEW_THRESHOLD => 'REVIEW',
            default => 'UNKNOWN',
        };
    }

    private function hasService(Project $project): bool
    {
        return DB::table('project_service')->where('project_id', $project->id)->exists();
    }
}
