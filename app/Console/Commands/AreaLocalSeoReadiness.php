<?php

namespace App\Console\Commands;

use App\Models\Area;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * What each district knows about itself - the inputs a Local SEO page
 * would be built from, if one were ever built.
 *
 * This creates nothing and publishes nothing. It answers a single
 * question the next phase depends on: for a given area, how much real,
 * evidenced material exists to justify a page? Verified projects,
 * services, cleared images, case studies, articles - all counted from
 * relations that have rows, never guessed.
 *
 * A district with zero verified projects is shown as not ready, in red,
 * so the line between "has a page's worth of evidence" and "does not" is
 * never blurred.
 */
class AreaLocalSeoReadiness extends Command
{
    protected $signature = 'areas:local-seo-readiness {--ready : Only districts with at least one verified project}';

    protected $description = 'Count the evidence behind each district: verified projects, services, images, case studies, articles';

    public function handle(): int
    {
        $rows = [];
        $readyCount = 0;

        foreach (Area::query()->orderBy('tier')->orderBy('name')->get() as $area) {
            $verifiedProjects = $area->projects()
                ->where('location_status', 'verified')
                ->where('location_confidence', '>=', 3)
                ->get();

            $verifiedCount = $verifiedProjects->count();

            if ($this->option('ready') && $verifiedCount === 0) {
                continue;
            }

            if ($verifiedCount > 0) {
                $readyCount++;
            }

            $projectIds = $verifiedProjects->pluck('id');

            $services = $projectIds->isEmpty() ? 0 : DB::table('project_service')->whereIn('project_id', $projectIds)->distinct()->count('service_id');
            $caseStudies = $verifiedProjects->filter->hasCaseStudy()->count();
            $images = $projectIds->isEmpty() ? 0 : DB::table('project_media')->whereIn('project_id', $projectIds)->count();
            $articles = DB::table('article_area')->where('area_id', $area->id)->count();

            $score = $this->score($verifiedCount, $services, $caseStudies, $images, $articles);

            $rows[] = [
                $area->name,
                $area->tier->value,
                $verifiedCount,
                $services,
                $caseStudies,
                $images,
                $articles,
                $score.'/100',
                $this->readiness($score, $verifiedCount),
            ];
        }

        $this->table(
            ['الحي', 'الطبقة', 'مشاريع موثّقة', 'خدمات', 'دراسات حالة', 'صور', 'مقالات', 'SEO Score', 'الجاهزية'],
            $rows ?: [['—', '—', '—', '—', '—', '—', '—', '—', '—']],
        );

        $this->line('');
        $this->info("Districts with verified evidence: {$readyCount} of ".Area::count());

        if ($readyCount === 0) {
            $this->comment('No district has a verified project yet, so none is ready for a Local SEO page. Collect location evidence first (projects:location-audit).');
        }

        return self::SUCCESS;
    }

    /**
     * A transparent 0-100 readiness score. The weights encode what a real
     * Local SEO page needs to stand on: verified projects and the case
     * studies written from them carry the most, because they are the
     * evidence; services, images and articles round it out. It is a
     * planning aid, not a ranking prediction - there is no traffic data
     * here to predict one.
     */
    private function score(int $verified, int $services, int $caseStudies, int $images, int $articles): int
    {
        $score = min(35, $verified * 12)      // verified projects: the spine
            + min(25, $caseStudies * 12)      // written case studies
            + min(15, $services * 5)          // service variety
            + min(15, (int) ($images / 2))    // photographic depth
            + min(10, $articles * 3);         // supporting reading

        return min(100, $score);
    }

    private function readiness(int $score, int $verified): string
    {
        return match (true) {
            $verified === 0 => 'لا دليل',
            $score >= 70 => 'جاهز',
            $score >= 40 => 'شبه جاهز',
            default => 'يحتاج دليلًا أكثر',
        };
    }
}
