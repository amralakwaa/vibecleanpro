<?php

namespace App\Console\Commands;

use App\Enums\AreaTier;
use App\Models\Area;
use App\Models\Project;
use Illuminate\Console\Command;

/**
 * Where does the location layer stand, project by project.
 *
 * Three buckets, because three different things block a case study from
 * becoming a local signal:
 *   - no district at all (nothing to link to an area page);
 *   - a district but no source (a place we cannot account for);
 *   - district + source, ready to verify and use.
 *
 * A verified project is not listed - it is done. This command only shows
 * work that remains, and it writes nothing.
 */
class ProjectLocationAudit extends Command
{
    protected $signature = 'projects:location-audit {--ready : List only the projects ready to verify}';

    protected $description = 'Report projects missing a district, missing a source, or ready to link and verify';

    public function handle(): int
    {
        $noLocation = [];
        $unverified = [];
        $seoReady = [];
        $staleEvidence = [];
        $areaEvidence = [];
        $staleBefore = now()->subYears(2);

        foreach (Project::query()->with('area')->orderBy('source_ref')->get() as $project) {
            $hasDistrict = $project->area_id !== null || filled($project->neighborhood);

            if (! $hasDistrict) {
                $noLocation[] = [$project->source_ref, $project->title];

                continue;
            }

            if ($project->hasVerifiedLocation()) {
                $seoReady[] = [$project->source_ref, $project->area?->name ?? $project->neighborhood, $project->location_confidence?->value];

                if ($project->area_id) {
                    $areaEvidence[$project->area_id] = ($areaEvidence[$project->area_id] ?? 0) + 1;
                }

                // Section E: evidence a signal rests on can go stale. A
                // verified location older than two years is flagged for a
                // fresh look, not demoted automatically.
                if ($project->verified_at && $project->verified_at->lt($staleBefore)) {
                    $staleEvidence[] = [$project->source_ref, $project->area?->name ?? $project->neighborhood, $project->verified_at->format('Y-m-d')];
                }
            } else {
                $unverified[] = [
                    $project->source_ref,
                    $project->area?->name ?? $project->neighborhood,
                    $project->location_status?->label() ?? '—',
                    $project->location_confidence?->value ?? 0,
                ];
            }
        }

        // A district page may be promoted only when at least one project
        // there is SEO-ready. The tiers stay untouched here - this just
        // names the candidates ReevaluateAreaTiers would surface.
        $promotable = [];
        foreach ($areaEvidence as $areaId => $count) {
            $area = Area::find($areaId);
            if ($area && $area->tier !== AreaTier::A) {
                $promotable[] = [$area->name, $area->tier->value, $count];
            }
        }

        if ($this->option('ready')) {
            $this->info(count($seoReady).' project(s) READY for Local SEO (verified + confidence >= 3):');
            $this->table(['المرجع', 'الحي', 'مستوى الثقة'], $seoReady ?: [['—', '—', '—']]);

            return self::SUCCESS;
        }

        $this->line('');
        $this->warn(count($noLocation).' project(s) with NO location:');
        $this->table(['المرجع', 'العنوان'], $noLocation ?: [['—', '—']]);

        $this->warn(count($unverified).' project(s) with a location that is NOT SEO-verified:');
        $this->table(['المرجع', 'الحي', 'الحالة', 'الثقة'], $unverified ?: [['—', '—', '—', '—']]);

        $this->info(count($seoReady).' project(s) READY for Local SEO (verified + confidence >= 3):');
        $this->table(['المرجع', 'الحي', 'الثقة'], $seoReady ?: [['—', '—', '—']]);

        $this->info(count($promotable).' district(s) with SEO-ready evidence, candidate for promotion (tiers not changed):');
        $this->table(['الحي', 'الطبقة الحالية', 'مشاريع جاهزة'], $promotable ?: [['—', '—', '—']]);

        $this->warn(count($staleEvidence).' project(s) whose evidence is older than 2 years (re-review):');
        $this->table(['المرجع', 'الحي', 'تاريخ الاعتماد'], $staleEvidence ?: [['—', '—', '—']]);

        $this->line('');
        $this->info('No location: '.count($noLocation).' · Unverified: '.count($unverified).' · SEO-ready: '.count($seoReady).' · Promotion candidates: '.count($promotable).' · Stale: '.count($staleEvidence));

        return self::SUCCESS;
    }
}
