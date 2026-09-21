<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Seo\ProjectLocationScore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * A per-project view of the location layer: what each project knows about
 * where it happened, how strong that evidence is, and whether it may
 * power SEO.
 *
 * Strictly read-only. It classifies, it does not change anything - the
 * decision to verify or promote lives in the panel and the promotion
 * command, never here.
 */
class ProjectLocationIntelligence extends Command
{
    protected $signature = 'projects:location-intelligence
        {--eligibility= : Filter to VERIFIED, REVIEW or UNKNOWN}
        {--json= : Write the full report to this path as JSON}';

    protected $description = 'Read-only per-project location intelligence: status, evidence, score and SEO eligibility';

    public function handle(ProjectLocationScore $scorer): int
    {
        $rows = [];
        $report = [];
        $counts = ['VERIFIED' => 0, 'REVIEW REQUIRED' => 0, 'UNKNOWN' => 0];
        $filter = strtoupper((string) $this->option('eligibility'));

        foreach (Project::query()->with('area', 'services')->orderBy('source_ref')->get() as $project) {
            $eligibility = $scorer->eligibility($project);
            $counts[$eligibility] = ($counts[$eligibility] ?? 0) + 1;
            $score = $scorer->for($project);

            $record = [
                'source_ref' => $project->source_ref,
                'title' => $project->title,
                'service' => $project->services->first()?->name,
                'area' => $project->area?->name,
                'city' => $project->city,
                'neighborhood' => $project->neighborhood,
                'landmark' => $project->landmark,
                'location_status' => $project->location_status?->value,
                'location_confidence' => $project->location_confidence?->value,
                'evidence_type' => $project->location_evidence_type?->value,
                'evidence_reference' => $project->location_evidence_reference,
                'has_case_study' => $project->hasCaseStudy(),
                'has_images' => DB::table('project_media')->where('project_id', $project->id)->exists(),
                'location_score' => $score['score'],
                'score_band' => $score['band'],
                'seo_eligibility' => $eligibility,
            ];
            $report[] = $record;

            if ($filter !== '' && $eligibility !== $filter && ! ($filter === 'REVIEW' && $eligibility === 'REVIEW REQUIRED')) {
                continue;
            }

            $rows[] = [
                $project->source_ref,
                $project->services->first()?->name ?? '—',
                $project->area?->name ?? '—',
                $project->location_status?->label() ?? 'مسودة',
                $project->location_confidence?->value ?? 0,
                $project->location_evidence_type?->label() ?? '—',
                $record['has_case_study'] ? '✔' : '—',
                $record['has_images'] ? '✔' : '—',
                $score['score'].'/100',
                $eligibility,
            ];
        }

        $this->table(
            ['المرجع', 'الخدمة', 'الحي', 'الحالة', 'الثقة', 'نوع الدليل', 'دراسة', 'صور', 'Score', 'الأهلية'],
            $rows ?: [['—', '—', '—', '—', '—', '—', '—', '—', '—', '—']],
        );

        $this->line('');
        $this->info("VERIFIED: {$counts['VERIFIED']} · REVIEW REQUIRED: {$counts['REVIEW REQUIRED']} · UNKNOWN: {$counts['UNKNOWN']} · Total: ".array_sum($counts));

        if ($path = $this->option('json')) {
            file_put_contents($path, json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $this->info("Full report written to {$path}");
        }

        return self::SUCCESS;
    }
}
