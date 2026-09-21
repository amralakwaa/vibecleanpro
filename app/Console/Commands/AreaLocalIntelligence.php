<?php

namespace App\Console\Commands;

use App\Models\Area;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * A per-district readiness view for Local SEO, with a recommendation.
 *
 * Read-only. It answers "which districts have a real page's worth of
 * evidence, and which are only worth collecting more?" from relations
 * that have rows - verified projects, the services and case studies
 * behind them, images and articles. It builds nothing and promotes
 * nothing; the recommendation is advice for a human.
 *
 * The recommendation is deliberately conservative: a district is only
 * READY when it has a verified project AND a service AND either a case
 * study or images. Anything with interest but no verified project is
 * COLLECT EVIDENCE; anything empty is NOT READY. There is no path here
 * from "people search this name" to "ready" - only evidence opens that
 * door.
 */
class AreaLocalIntelligence extends Command
{
    protected $signature = 'areas:local-intelligence {--ready : Only districts recommended READY FOR AREA PAGE}';

    protected $description = 'Read-only per-district Local SEO intelligence: verified evidence, score and a recommendation';

    public function handle(): int
    {
        $rows = [];
        $recommendations = ['READY FOR AREA PAGE' => 0, 'COLLECT EVIDENCE' => 0, 'NOT READY' => 0];

        foreach (Area::query()->orderBy('tier')->orderBy('name')->get() as $area) {
            $verified = $area->projects()
                ->where('location_status', 'verified')
                ->where('location_confidence', '>=', 3)
                ->whereNotNull('location_evidence_type')
                ->whereNotNull('location_evidence_reference')
                ->get();

            $verifiedIds = $verified->pluck('id');
            $services = $verifiedIds->isEmpty() ? 0 : DB::table('project_service')->whereIn('project_id', $verifiedIds)->distinct()->count('service_id');
            $caseStudies = $verified->filter->hasCaseStudy()->count();
            $images = $verifiedIds->isEmpty() ? 0 : DB::table('project_media')->whereIn('project_id', $verifiedIds)->count();
            $articles = DB::table('article_area')->where('area_id', $area->id)->count();

            $score = $this->score($verified->count(), $services, $caseStudies, $images, $articles);
            $recommendation = $this->recommend($verified->count(), $services, $caseStudies, $images);
            $recommendations[$recommendation]++;

            if ($this->option('ready') && $recommendation !== 'READY FOR AREA PAGE') {
                continue;
            }

            // The table lists districts with any signal; a fully empty
            // district is counted but not printed, to keep 86 rows of
            // zeros from burying the ones that matter.
            if ($verified->count() === 0 && ! $this->option('ready') && $score === 0) {
                continue;
            }

            $rows[] = [
                $area->name,
                $area->tier->value,
                $verified->count(),
                $services,
                $caseStudies,
                $images,
                $articles,
                $score.'/100',
                $recommendation,
            ];
        }

        $this->table(
            ['الحي', 'الطبقة', 'مشاريع موثّقة', 'خدمات', 'دراسات حالة', 'صور', 'مقالات', 'SEO Score', 'التوصية'],
            $rows ?: [['—', '—', '—', '—', '—', '—', '—', '—', '—']],
        );

        $this->line('');
        $this->info('READY: '.$recommendations['READY FOR AREA PAGE'].' · COLLECT EVIDENCE: '.$recommendations['COLLECT EVIDENCE'].' · NOT READY: '.$recommendations['NOT READY'].' · Total: '.Area::count());

        if ($recommendations['READY FOR AREA PAGE'] === 0) {
            $this->comment('No district is ready for a Local SEO page. That is correct until a project is verified in one - collect evidence first (projects:location-audit).');
        }

        return self::SUCCESS;
    }

    private function score(int $verified, int $services, int $caseStudies, int $images, int $articles): int
    {
        return min(100,
            min(35, $verified * 12)
            + min(25, $caseStudies * 12)
            + min(15, $services * 5)
            + min(15, (int) ($images / 2))
            + min(10, $articles * 3),
        );
    }

    private function recommend(int $verified, int $services, int $caseStudies, int $images): string
    {
        if ($verified >= 1 && $services >= 1 && ($caseStudies >= 1 || $images >= 1)) {
            return 'READY FOR AREA PAGE';
        }

        if ($verified >= 1 || $services >= 1) {
            return 'COLLECT EVIDENCE';
        }

        return 'NOT READY';
    }
}
