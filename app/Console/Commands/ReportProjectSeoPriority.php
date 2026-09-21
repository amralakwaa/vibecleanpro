<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Models\Project;
use App\Seo\ProjectSeoPriority;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Prints the case-study priority table: which project earns the next
 * photo session, the next article, the next round of copy.
 *
 * It writes nothing. Publishing and indexing are decided elsewhere.
 */
class ReportProjectSeoPriority extends Command
{
    protected $signature = 'projects:seo-priority
        {--tier= : Show one tier only (A, B or C)}
        {--store : Also persist score, tier and the time they were calculated}';

    protected $description = 'Score every case study on search, commercial, media, conversion and authority value';

    /**
     * The score is always a calculation; --store writes a snapshot of it
     * so the panel can sort by priority and show how old that ranking is.
     * Re-running refreshes it - nothing here goes stale silently.
     */
    private int $logged = 0;

    public function handle(ProjectSeoPriority $priority): int
    {
        $filter = strtoupper((string) $this->option('tier'));
        $rows = [];
        $counts = ['A' => 0, 'B' => 0, 'C' => 0];

        $stored = 0;

        foreach ($priority->ranked() as $entry) {
            $project = $entry['project'];
            $counts[$entry['tier']]++;

            if ($this->option('store')) {
                $stored += $this->store($project, $entry);
            }

            if ($filter !== '' && $entry['tier'] !== $filter) {
                continue;
            }

            $page = Page::query()->where('type', 'project')->where('pageable_id', $project->id)->first();

            $rows[] = [
                $entry['tier'],
                $entry['score'],
                $page?->slug ?? $project->source_ref,
                $page?->status->value ?? '-',
                implode(' ', array_map(
                    fn (string $key, int $value) => strtoupper($key[0]).$value,
                    array_keys($entry['axes']),
                    $entry['axes'],
                )),
            ];
        }

        $this->table(['Tier', 'Score', 'Case study', 'Status', 'S/C/M/V/A'], $rows);
        $this->info("Tier A: {$counts['A']} · Tier B: {$counts['B']} · Tier C: {$counts['C']}");

        if ($stored > 0) {
            $this->info("Stored the score and tier on {$stored} project(s).");
        }
        if ($this->logged > 0) {
            $this->info("Recorded {$this->logged} priority change(s) in the history.");
        }

        $this->line('Axes: S search opportunity · C commercial value · M media strength · V conversion value · A authority value (each 0-5).');

        return self::SUCCESS;
    }

    /**
     * Writes the snapshot, and logs the change only when there is one.
     * A project whose score did not move produces no history row.
     *
     * @param  array{score: int, tier: string, axes: array<string, int>}  $entry
     */
    private function store(Project $project, array $entry): int
    {
        $oldScore = $project->seo_priority_score;
        $oldTier = $project->seo_priority_tier;
        $changed = $oldScore !== $entry['score'] || $oldTier !== $entry['tier'];

        $project->forceFill([
            'seo_priority_score' => $entry['score'],
            'seo_priority_tier' => $entry['tier'],
            'seo_priority_updated_at' => now(),
        ])->save();

        if (! $changed) {
            return 1;
        }

        DB::table('project_seo_priority_history')->insert([
            'project_id' => $project->id,
            'old_score' => $oldScore,
            'new_score' => $entry['score'],
            'old_tier' => $oldTier,
            'new_tier' => $entry['tier'],
            'reason' => $this->reason($project, $oldScore, $entry),
            'axes' => json_encode($entry['axes'], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
        ]);

        $this->logged++;

        return 1;
    }

    /**
     * The reason is built by diffing this run's axes against the axes
     * stored with the previous change. Without a previous record there is
     * nothing to compare, and the row says so rather than inventing a
     * cause.
     *
     * @param  array{score: int, tier: string, axes: array<string, int>}  $entry
     */
    private function reason(Project $project, ?int $oldScore, array $entry): string
    {
        $labels = [
            'search_opportunity' => 'الطلب على كلمات الخدمة',
            'commercial_value' => 'القيمة التجارية للخدمة',
            'media_strength' => 'الرصيد المصوَّر',
            'conversion_value' => 'عناصر الإقناع (قبل/بعد وأسئلة وخدمة)',
            'authority_value' => 'عمق المحتوى وخطوات التنفيذ',
        ];

        $previous = DB::table('project_seo_priority_history')
            ->where('project_id', $project->id)
            ->latest('created_at')
            ->value('axes');

        if ($oldScore === null) {
            return 'أول حساب مسجَّل لهذا المشروع.';
        }

        if (! $previous) {
            $direction = $entry['score'] > $oldScore ? 'ارتفعت' : 'انخفضت';

            return "النتيجة {$direction} من {$oldScore} إلى {$entry['score']}. لا توجد قراءة محاور سابقة للمقارنة التفصيلية.";
        }

        $before = json_decode((string) $previous, true) ?: [];
        $moves = [];

        foreach ($entry['axes'] as $axis => $value) {
            $delta = $value - (int) ($before[$axis] ?? 0);

            if ($delta !== 0) {
                $moves[] = $labels[$axis].($delta > 0 ? " +{$delta}" : " {$delta}");
            }
        }

        return $moves === []
            ? "تغيّرت النتيجة من {$oldScore} إلى {$entry['score']} دون تغيّر في المحاور."
            : 'تغيّر: '.implode(' · ', $moves).'.';
    }
}
