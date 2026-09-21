<?php

namespace App\Console\Commands;

use App\Enums\AreaTier;
use App\Models\Area;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Re-decides each area's tier from evidence, not from a seed default.
 *
 * Tier A is a promise: a local page Google is allowed to index. That
 * promise is only honest when the area actually has something local to
 * show - a project done there, a photograph taken there, a case study
 * about it. The catalogue shipped nine areas at Tier A with none of that
 * and no recorded reason, which is nine doorway pages waiting to be
 * published.
 *
 * So the rule here is one-directional and conservative:
 *   - an area at Tier A with no evidence is demoted to B, and the reason
 *     is written down;
 *   - an area at B or C that HAS evidence is reported as a promotion
 *     candidate, never promoted automatically - putting a page into the
 *     index is a human decision with a human reason;
 *   - Tier C is never changed silently, and no area is ever deleted.
 *
 * Evidence = at least one project linked to the area (a real job with a
 * location), or at least one photograph carrying the area. A project that
 * is also a written case study counts double in the report, because that
 * is what a Tier A page would actually be built from.
 *
 * Dry-run by default. --apply performs the demotions.
 */
class ReevaluateAreaTiers extends Command
{
    protected $signature = 'areas:reevaluate-tiers {--apply : Perform the demotions instead of only listing them}';

    protected $description = 'Demote evidence-less Tier A areas to B, and report B/C areas that now have evidence';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $demoted = [];
        $candidates = [];
        $keptA = [];

        foreach (Area::query()->orderBy('tier')->orderBy('name')->get() as $area) {
            $projects = $area->projects()->count();
            $caseStudies = $area->projects()->whereNotNull('challenge')->count();
            $media = DB::table('media')->where('area_id', $area->id)->count();
            $hasEvidence = $projects > 0 || $media > 0;

            if ($area->tier === AreaTier::A && ! $hasEvidence) {
                $demoted[] = [$area->name, $projects, $media];

                if ($apply) {
                    $area->forceFill([
                        'tier' => AreaTier::B,
                        'promoted_at' => null,
                        'promotion_reason' => 'أُنزل إلى B تلقائيًا ('.now()->toDateString().'): لا مشروع مرتبط ولا صور ولا دراسة حالة في هذا الحي. يعود إلى A عند ظهور دليل حقيقي.',
                    ])->save();
                }
            } elseif ($area->tier === AreaTier::A) {
                $keptA[] = [$area->name, $projects, $caseStudies, $media];
            } elseif ($hasEvidence) {
                $candidates[] = [$area->tier->value, $area->name, $projects, $caseStudies, $media];
            }
        }

        $this->line('');
        $this->info(($apply ? 'Demoted' : 'Would demote').' '.count($demoted).' evidence-less Tier A area(s) to B:');
        $this->table(['الحي', 'مشاريع', 'صور'], $demoted ?: [['—', '—', '—']]);

        if ($keptA !== []) {
            $this->info(count($keptA).' Tier A area(s) kept - they carry evidence:');
            $this->table(['الحي', 'مشاريع', 'دراسات حالة', 'صور'], $keptA);
        }

        $this->info(count($candidates).' area(s) below A now carry evidence (promotion is a human decision - not applied):');
        $this->table(['الطبقة', 'الحي', 'مشاريع', 'دراسات حالة', 'صور'], $candidates ?: [['—', '—', '—', '—', '—']]);

        $counts = Area::query()->selectRaw('tier, count(*) c')->groupBy('tier')->pluck('c', 'tier');
        $this->line('');
        $this->info('Tier distribution '.($apply ? 'now' : 'currently').': A='.($counts['a'] ?? 0).' · B='.($counts['b'] ?? 0).' · C='.($counts['c'] ?? 0));

        if (! $apply && $demoted !== []) {
            $this->comment('Dry run. Re-run with --apply to perform the demotions.');
        }

        return self::SUCCESS;
    }
}
