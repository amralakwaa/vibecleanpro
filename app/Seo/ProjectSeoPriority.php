<?php

namespace App\Seo;

use App\Models\Page;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

/**
 * Ranks case studies by the business they can win, not by how they look.
 *
 * Thirty-four case studies cannot all receive the same attention: the
 * next photo session, the next article and the next round of copy should
 * go where they move revenue. This scores each project on five axes and
 * sorts it into a tier, so that decision is made from evidence instead of
 * instinct.
 *
 * It changes nothing about publishing or indexing. It is advice.
 *
 * The five axes:
 *  - Search opportunity   how much demand the supported service's queries carry
 *  - Commercial value     what a job in that service is worth to the business
 *  - Media strength       how much cleared evidence the page can show
 *  - Conversion value     whether the page can turn a reader into an enquiry
 *  - Authority value      whether the work is hard to imitate and worth citing
 *
 * Every input is read from the database or from the entity map. Nothing
 * here is a guess about traffic - there is no keyword-volume source in
 * this project, so "search opportunity" is deliberately a proxy built
 * from search intent and service tier, and it is named as one.
 */
class ProjectSeoPriority
{
    /**
     * What a won job in this service is worth, relative to the others:
     * high-ticket or contract-bearing work at the top, one-off domestic
     * jobs at the bottom. Facade and post-construction lead because they
     * are priced per building; offices and contracts lead because one
     * signature repeats monthly.
     */
    private const COMMERCIAL_VALUE = [
        'facade-cleaning' => 5,
        'cleaning-contracts' => 5,
        'post-construction-cleaning' => 5,
        'office-cleaning' => 4,
        'shop-cleaning' => 4,
        'glass-cleaning' => 3,
        'marble-polishing' => 3,
        'villa-cleaning' => 3,
        'courtyard-cleaning' => 3,
        'pool-cleaning' => 2,
        'water-tank-cleaning' => 2,
        'home-cleaning' => 2,
        'apartment-cleaning' => 2,
        'disinfection' => 2,
        'pest-control' => 2,
        'sofa-cleaning' => 1,
        'carpet-cleaning' => 1,
        'majlis-cleaning' => 1,
        'ac-cleaning' => 1,
    ];

    /** Search intent as a demand proxy, in the absence of volume data. */
    private const INTENT_WEIGHT = [
        'transactional' => 5,
        'commercial' => 3,
        'informational' => 2,
    ];

    public function __construct(private ServiceEntityMap $entityMap) {}

    /**
     * @return array{score: int, tier: string, axes: array<string, int>}
     */
    public function for(Project $project): array
    {
        $page = Page::query()->where('type', 'project')->where('pageable_id', $project->id)->first();
        $slugs = $this->serviceSlugs($project);

        $axes = [
            'search_opportunity' => $this->searchOpportunity($slugs),
            'commercial_value' => $this->commercialValue($slugs),
            'media_strength' => $this->mediaStrength($project),
            'conversion_value' => $this->conversionValue($project, $page),
            'authority_value' => $this->authorityValue($project),
        ];

        $score = array_sum($axes);

        return [
            'score' => $score,
            'tier' => match (true) {
                $score >= 19 => 'A',
                $score >= 14 => 'B',
                default => 'C',
            },
            'axes' => $axes,
        ];
    }

    /**
     * Why this project scored what it scored, in the language of the
     * person who has to act on it. A tier with no reasoning is a number
     * someone will argue with; a tier that names its strengths and its
     * one missing piece is a work order.
     *
     * @return array{tier: string, score: int, strengths: list<string>, gaps: list<string>, verdict: string}
     */
    public function explain(Project $project): array
    {
        $result = $this->for($project);
        $axes = $result['axes'];

        $labels = [
            'search_opportunity' => ['قوة الطلب على كلمات الخدمة', 'الخدمة المرتبطة ذات طلب بحث محدود'],
            'commercial_value' => ['الخدمة عالية القيمة التجارية', 'الخدمة منخفضة العائد لكل عملية'],
            'media_strength' => ['رصيد مصوَّر قوي يغطي أكثر من مرحلة', 'الصور قليلة ومن مرحلة واحدة'],
            'conversion_value' => ['يملك ما يقنع القارئ: قبل/بعد أو أسئلة أو خدمة واضحة', 'ينقصه دليل مقارنة قبل/بعد'],
            'authority_value' => ['محتوى عميق بخطوات تنفيذ مفصّلة', 'المحتوى قصير أو خطواته ناقصة'],
        ];

        $strengths = [];
        $gaps = [];

        foreach ($axes as $axis => $value) {
            if ($value >= 4) {
                $strengths[] = $labels[$axis][0];
            } elseif ($value <= 2) {
                $gaps[] = $labels[$axis][1];
            }
        }

        $verdict = match ($result['tier']) {
            'A' => 'أولوية قصوى: يستحق أول جلسة تصوير وأول مقال داعم، ويصلح واجهة لمكتبة الأعمال.',
            'B' => 'أولوية متوسطة: جاهز ومنشور، ويرتقي إلى Tier A بإضافة صور مرحلة ثانية أو زوج قبل/بعد.',
            default => 'أولوية متأخرة: يحتاج رصيدًا مصوَّرًا أو محتوى أعمق قبل أن يستحق استثمارًا إضافيًا.',
        };

        return [
            'tier' => $result['tier'],
            'score' => $result['score'],
            'strengths' => $strengths,
            'gaps' => $gaps,
            'verdict' => $verdict,
        ];
    }

    /**
     * @return array<int, array{project: Project, score: int, tier: string, axes: array<string, int>}>
     */
    public function ranked(): array
    {
        $rows = Project::query()
            ->with('services.page')
            ->get()
            ->map(fn (Project $project) => ['project' => $project] + $this->for($project))
            ->sortByDesc('score')
            ->values()
            ->all();

        return $rows;
    }

    /**
     * @return list<string>
     */
    private function serviceSlugs(Project $project): array
    {
        return $project->services
            ->map(fn ($service) => Page::query()->where('type', 'service')->where('pageable_id', $service->id)->value('slug'))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $slugs
     */
    private function searchOpportunity(array $slugs): int
    {
        $best = 0;

        foreach ($slugs as $slug) {
            $entry = $this->entityMap->for($slug);

            if (! $entry) {
                continue;
            }

            // Intent carries most of it; a service that owns more phrases
            // gives the case study more doors to enter through.
            $phrases = min(2, (int) floor(count($entry['secondary']) / 3));
            $best = max($best, (self::INTENT_WEIGHT[$entry['intent']] ?? 2) + $phrases);
        }

        return min(5, $best ?: 2);
    }

    /**
     * @param  list<string>  $slugs
     */
    private function commercialValue(array $slugs): int
    {
        $best = 0;

        foreach ($slugs as $slug) {
            $best = max($best, self::COMMERCIAL_VALUE[$slug] ?? 2);
        }

        return $best ?: 1;
    }

    private function mediaStrength(Project $project): int
    {
        $photos = DB::table('project_media')
            ->join('media', 'media.id', '=', 'project_media.media_id')
            ->where('project_media.project_id', $project->id)
            ->whereIn('media.status', ['ready', 'replace'])
            ->count();

        $stages = DB::table('project_media')
            ->join('media', 'media.id', '=', 'project_media.media_id')
            ->where('project_media.project_id', $project->id)
            ->whereIn('media.status', ['ready', 'replace'])
            ->distinct()
            ->count('project_media.stage');

        $volume = match (true) {
            $photos >= 12 => 4,
            $photos >= 6 => 3,
            $photos >= 3 => 2,
            $photos >= 1 => 1,
            default => 0,
        };

        // More than one stage means the page can tell a sequence, not
        // just show a moment.
        return min(5, $volume + ($stages > 1 ? 1 : 0));
    }

    private function conversionValue(Project $project, ?Page $page): int
    {
        $score = 0;

        $hasBefore = $this->hasStage($project, 'before');
        $hasAfter = $this->hasStage($project, 'after');

        // A documented before/after from one site is the single most
        // persuasive thing a cleaning page can carry.
        $score += ($hasBefore && $hasAfter) ? 3 : (($hasBefore || $hasAfter) ? 1 : 0);
        $score += $page && DB::table('faqs')->where('page_id', $page->id)->where('is_active', true)->exists() ? 1 : 0;
        $score += $project->services()->exists() ? 1 : 0;

        return min(5, $score);
    }

    private function authorityValue(Project $project): int
    {
        $words = str_word_count(
            strip_tags((string) $project->challenge.' '.(string) $project->site_condition.' '.(string) $project->outcome),
            0,
            'أبتثجحخدذرزسشصضطظعغفقكلمنهوياةىئءؤإأآّ'
        );

        $depth = match (true) {
            $words >= 140 => 3,
            $words >= 100 => 2,
            $words >= 60 => 1,
            default => 0,
        };

        $steps = is_array($project->execution_steps) ? count($project->execution_steps) : 0;

        return min(5, $depth + ($steps >= 4 ? 2 : ($steps >= 3 ? 1 : 0)));
    }

    private function hasStage(Project $project, string $stage): bool
    {
        return DB::table('project_media')
            ->join('media', 'media.id', '=', 'project_media.media_id')
            ->where('project_media.project_id', $project->id)
            ->where('project_media.stage', $stage)
            ->whereIn('media.status', ['ready', 'replace'])
            ->exists();
    }
}
