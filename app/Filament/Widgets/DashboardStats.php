<?php

namespace App\Filament\Widgets;

use App\Enums\LeadStatus;
use App\Enums\PageStatus;
use App\Models\Lead;
use App\Models\Media;
use App\Models\Page;
use App\Seo\SeoDashboardService;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

/**
 * The top strip of the dashboard: four numbers, not nine.
 *
 * The previous version printed every count the application could produce -
 * services, areas, projects, articles, pages, leads - as nine identical
 * white cards. Nine equal cards is the same as no hierarchy at all: nothing
 * is emphasised, so nothing is read, and the two figures that actually
 * change behaviour sat level with six that only ever go up.
 *
 * What survives is what the owner can act on this morning:
 *   - requests this month, against last month, because that is the business;
 *   - what is waiting in his court (drafts and pages in review);
 *   - whether the published site has SEO problems;
 *   - whether the photo library is describable to a screen reader.
 *
 * The inventory counts moved to ContentMixChart, where their proportions
 * say something a bare number cannot. Every stat carries a description that
 * states the consequence: a figure with nothing to compare it to is a fact
 * without a meaning.
 */
class DashboardStats extends BaseWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $user = Auth::user();
        $stats = [];

        if ($user?->can('view_any_lead')) {
            $stats[] = $this->leadsStat();
        }

        if ($user?->can('view_any_page')) {
            $stats[] = $this->awaitingYouStat();
            $stats[] = $this->seoHealthStat();
        }

        if ($user?->can('view_any_media')) {
            $stats[] = $this->mediaStat();
        }

        return $stats;
    }

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user && ($user->can('view_any_lead') || $user->can('view_any_page'));
    }

    /**
     * Requests this month, with last month as the comparison and the last
     * six months as a sparkline. One month in isolation says nothing; the
     * direction is the entire signal.
     */
    private function leadsStat(): Stat
    {
        $thisMonth = Lead::query()
            ->whereBetween('created_at', [now()->startOfMonth(), now()])
            ->count();

        $lastMonth = Lead::query()
            ->whereBetween('created_at', [
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth(),
            ])
            ->count();

        $trend = collect(range(5, 0))
            ->map(fn (int $back): int => Lead::query()
                ->whereBetween('created_at', [
                    now()->subMonthsNoOverflow($back)->startOfMonth(),
                    now()->subMonthsNoOverflow($back)->endOfMonth(),
                ])
                ->count())
            ->all();

        [$description, $icon, $color] = match (true) {
            $thisMonth === 0 && $lastMonth === 0 => ['لم يصل أي طلب بعد', Heroicon::OutlinedMinus, 'gray'],
            $lastMonth === 0 => ['بعد شهر بلا طلبات', Heroicon::OutlinedArrowTrendingUp, 'success'],
            $thisMonth >= $lastMonth => ['مقابل '.$lastMonth.' الشهر الماضي', Heroicon::OutlinedArrowTrendingUp, 'success'],
            default => ['مقابل '.$lastMonth.' الشهر الماضي', Heroicon::OutlinedArrowTrendingDown, 'danger'],
        };

        $untouched = Lead::query()->where('status', LeadStatus::New)->count();

        $stat = Stat::make('طلبات هذا الشهر', (string) $thisMonth)
            ->description($untouched > 0
                ? $untouched.' لم يُتواصل معه بعد · '.$description
                : $description)
            ->descriptionIcon($untouched > 0 ? Heroicon::OutlinedExclamationCircle : $icon)
            ->descriptionColor($untouched > 0 ? 'warning' : $color)
            ->icon(Heroicon::OutlinedInbox);

        // A sparkline over six identical months is a straight line, which
        // reads as a stray border rather than as a trend and tells the
        // viewer nothing they did not already get from the number. It is
        // drawn only once the months actually differ.
        if (count(array_unique($trend)) > 1) {
            $stat->chart($trend)->chartColor($color === 'danger' ? 'danger' : 'primary');
        }

        return $stat;
    }

    /**
     * Work sitting in the owner's court. Zero is the healthy state here, so
     * it reads as calm rather than as an achievement.
     */
    private function awaitingYouStat(): Stat
    {
        $drafts = Page::query()->where('status', PageStatus::Draft)->count();
        $review = Page::query()->where('status', PageStatus::Review)->count();
        $waiting = $drafts + $review;

        return Stat::make('بانتظارك', (string) $waiting)
            ->description($waiting === 0
                ? 'لا مسودات ولا صفحات قيد المراجعة'
                : $drafts.' مسودة · '.$review.' قيد المراجعة')
            ->descriptionIcon($waiting === 0 ? Heroicon::OutlinedCheckCircle : Heroicon::OutlinedPencilSquare)
            ->descriptionColor($waiting === 0 ? 'success' : 'warning')
            ->icon(Heroicon::OutlinedClipboardDocumentList);
    }

    /**
     * Published pages that trip a Publishing Gate check. rescue() because
     * the gate walks every page, and this strip must never be the reason
     * the whole dashboard fails to render.
     */
    private function seoHealthStat(): Stat
    {
        $summary = rescue(fn () => app(SeoDashboardService::class)->summary(), null, false);

        if ($summary === null) {
            return Stat::make('ملاحظات SEO', '—')
                ->description('تعذّر حساب الفحص الآن')
                ->descriptionColor('gray')
                ->icon(Heroicon::OutlinedMagnifyingGlass);
        }

        $issues = $summary->pagesWithErrors + $summary->pagesWithWarnings;

        return Stat::make('صفحات بها ملاحظات SEO', (string) $issues)
            ->description($issues === 0
                ? $summary->indexable.' صفحة مفهرسة، كلها سليمة'
                : $summary->indexable.' مفهرسة · '.$summary->pagesWithErrors.' بها أخطاء')
            ->descriptionIcon($issues === 0 ? Heroicon::OutlinedCheckCircle : Heroicon::OutlinedExclamationTriangle)
            ->descriptionColor(match (true) {
                $summary->pagesWithErrors > 0 => 'danger',
                $issues > 0 => 'warning',
                default => 'success',
            })
            ->icon(Heroicon::OutlinedMagnifyingGlass);
    }

    /**
     * Alt-text coverage. It earns a place here because it is the one content
     * debt invisible everywhere else: nothing in the panel reports a missing
     * alt until the page carrying it is already published and flagged.
     */
    private function mediaStat(): Stat
    {
        $total = Media::query()->count();
        $described = Media::query()->whereNotNull('alt_text')->where('alt_text', '!=', '')->count();
        $percent = $total > 0 ? (int) round($described / $total * 100) : 100;

        return Stat::make('صور موصوفة', $percent.'%')
            ->description($total === $described
                ? 'كل الصور لها نص بديل'
                : ($total - $described).' صورة بلا نص بديل من '.$total)
            ->descriptionIcon($percent === 100 ? Heroicon::OutlinedCheckCircle : Heroicon::OutlinedPhoto)
            ->descriptionColor(match (true) {
                $percent >= 95 => 'success',
                $percent >= 70 => 'warning',
                default => 'danger',
            })
            ->icon(Heroicon::OutlinedPhoto);
    }
}
