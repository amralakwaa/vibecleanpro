<?php

namespace App\Filament\Widgets;

use App\Enums\PageStatus;
use App\Models\Media;
use App\Models\Page;
use App\Seo\SeoDashboardService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

/**
 * Sidra's "حالة SEO" panel, rebuilt against this application's data.
 *
 * It is the best idea on that dashboard and the one worth carrying over
 * wholesale: instead of printing raw counts, every check is a share of a
 * whole with a bar and a plain verdict. "103 صفحة مفهرسة" tells an owner
 * nothing unless he already knows the denominator; a bar at 94% with the
 * word ممتاز beside it is readable in a glance and needs no arithmetic.
 *
 * Each row answers a question the Publishing Gate already decides per page,
 * but aggregated, so the dashboard says how the SITE is doing rather than
 * how one page is doing.
 *
 * Beneath the bars sits the diagnostic strip: the counts that are not a
 * share of anything - orphans, broken links, similar pages - where the
 * healthy answer is simply zero. Those used to be eleven full-size stat
 * cards of their own, which is most of what made this dashboard long.
 *
 * @phpstan-type HealthRow array{label: string, done: int, total: int, percent: int, verdict: string, tone: string, hint: string}
 * @phpstan-type DiagnosticRow array{label: string, value: int, bad: bool}
 */
class SeoHealthWidget extends Widget
{
    protected string $view = 'filament.widgets.seo-health';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return (bool) Auth::user()?->can('view_any_page');
    }

    /**
     * @return array{rows: list<HealthRow>, diagnostics: list<DiagnosticRow>}
     */
    protected function getViewData(): array
    {
        $published = Page::query()
            ->where('status', PageStatus::Published)
            ->with('seoMetadata')
            ->get();

        $total = $published->count();

        $withTitle = $published->filter(fn (Page $page) => filled($page->seoMetadata?->meta_title))->count();
        $withDescription = $published->filter(fn (Page $page) => filled($page->seoMetadata?->meta_description))->count();

        $mediaTotal = Media::query()->count();
        $mediaDescribed = Media::query()->whereNotNull('alt_text')->where('alt_text', '!=', '')->count();

        // Google truncates SERP titles at ~600px, not at a fixed character count.
        // Arabic glyphs are narrower than Latin ones; at the SERP rendering size
        // an Arabic title fits roughly 80 characters before hitting that limit —
        // vs. the often-quoted 60 chars, which is a Latin-derived heuristic that
        // understates how much text Arabic can carry.
        $titlesThatFit = $published
            ->filter(fn (Page $page) => filled($page->seoMetadata?->meta_title)
                && mb_strlen($page->seoMetadata->meta_title) <= 80)
            ->count();

        return [
            'rows' => [
                $this->row('الصفحات مع Meta Title', $withTitle, $total, 'كل صفحة منشورة لها عنوان مخصص في نتائج البحث.'),
                $this->row('الصفحات مع Meta Description', $withDescription, $total, 'الوصف هو السطر الذي يقرؤه الباحث تحت العنوان.'),
                $this->row('عناوين لا يقطعها Google', $titlesThatFit, $total, 'قوقل يقطع عند ~600 بكسل؛ للعربية هذا يعادل نحو 80 حرفاً.'),
                $this->row('الصور مع Alt Text', $mediaDescribed, $mediaTotal, 'الصورة بلا نص بديل لا يقرؤها قارئ الشاشة ولا يفهرسها بحث الصور.'),
            ],
            'diagnostics' => $this->diagnostics(),
        ];
    }

    /**
     * Counts whose healthy value is zero, so they are shown as plain figures
     * rather than as a share of something. rescue() because the gate walks
     * every published page and this panel must not be the reason the
     * dashboard fails to render.
     *
     * @return list<DiagnosticRow>
     */
    private function diagnostics(): array
    {
        $summary = rescue(fn () => app(SeoDashboardService::class)->summary(), null, false);

        if ($summary === null) {
            return [];
        }

        return [
            ['label' => 'صفحات بها أخطاء', 'value' => $summary->pagesWithErrors, 'bad' => $summary->pagesWithErrors > 0],
            ['label' => 'صفحات بها تنبيهات', 'value' => $summary->pagesWithWarnings, 'bad' => $summary->pagesWithWarnings > 0],
            ['label' => 'روابط داخلية معطوبة', 'value' => $summary->brokenInternalLinks, 'bad' => $summary->brokenInternalLinks > 0],
            ['label' => 'صفحات يتيمة', 'value' => $summary->orphanPages, 'bad' => $summary->orphanPages > 0],
            ['label' => 'مناطق متشابهة', 'value' => $summary->similarLocalPagePairs, 'bad' => $summary->similarLocalPagePairs > 0],
            ['label' => 'تحويلات نشطة', 'value' => $summary->activeRedirects, 'bad' => false],
        ];
    }

    /**
     * @return HealthRow
     */
    private function row(string $label, int $done, int $total, string $hint): array
    {
        // An empty denominator is "nothing to get wrong", not a failure, so
        // it reads as complete rather than as a red bar at zero.
        $percent = $total > 0 ? (int) round($done / $total * 100) : 100;

        [$verdict, $tone] = match (true) {
            $percent >= 95 => ['ممتاز', 'good'],
            $percent >= 80 => ['جيد', 'brand'],
            $percent >= 50 => ['يحتاج تحسين', 'warn'],
            default => ['يحتاج اهتمام', 'bad'],
        };

        return [
            'label' => $label,
            'done' => $done,
            'total' => $total,
            'percent' => $percent,
            'verdict' => $verdict,
            'tone' => $tone,
            'hint' => $hint,
        ];
    }
}
