<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Redirects\RedirectResource;
use App\Seo\SeoDashboardService;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SeoDashboardWidget extends BaseWidget
{
    /**
     * Superseded by SeoHealthWidget, which presents the same figures the way
     * Sidra's dashboard does - four shares with bars and a verdict, then the
     * zero-is-healthy counts as a compact strip - instead of as eleven
     * full-size stat cards, which were most of this dashboard's length.
     *
     * The class is kept rather than deleted: SeoDashboardService and its
     * tests are shared with SeoHealthWidget, and returning true here is the
     * one-line way back if the card layout is ever wanted again.
     */
    public static function canView(): bool
    {
        return false;
    }

    protected function getStats(): array
    {
        $summary = app(SeoDashboardService::class)->summary();

        return [
            Stat::make('صفحات قابلة للفهرسة', (string) $summary->indexable)
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success'),
            Stat::make('صفحات Noindex', (string) $summary->noindex)
                ->icon(Heroicon::OutlinedEyeSlash),
            Stat::make('مسودات', (string) $summary->draft)
                ->icon(Heroicon::OutlinedPencil),
            Stat::make('قيد المراجعة', (string) $summary->review)
                ->icon(Heroicon::OutlinedClock)
                ->color('info'),
            Stat::make('صفحات بها أخطاء SEO', (string) $summary->pagesWithErrors)
                ->icon(Heroicon::OutlinedXCircle)
                ->color($summary->pagesWithErrors > 0 ? 'danger' : 'success')
                ->url(PageResource::getUrl('index')),
            Stat::make('صفحات بها تنبيهات', (string) $summary->pagesWithWarnings)
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color($summary->pagesWithWarnings > 0 ? 'warning' : 'success'),
            Stat::make('صفحات يتيمة (بلا روابط واردة)', (string) $summary->orphanPages)
                ->icon(Heroicon::OutlinedLink)
                ->color($summary->orphanPages > 0 ? 'warning' : 'success'),
            Stat::make('تحويلات (Redirects) نشطة', (string) $summary->activeRedirects)
                ->icon(Heroicon::OutlinedArrowsRightLeft)
                ->url(RedirectResource::getUrl('index')),
            Stat::make('روابط داخلية معطوبة', (string) $summary->brokenInternalLinks)
                ->icon(Heroicon::OutlinedExclamationCircle)
                ->color($summary->brokenInternalLinks > 0 ? 'danger' : 'success'),
            Stat::make('صفحات ينقصها بيانات SEO أساسية', (string) $summary->missingMetadata)
                ->icon(Heroicon::OutlinedDocumentMagnifyingGlass)
                ->color($summary->missingMetadata > 0 ? 'warning' : 'success'),
            Stat::make('صفحات مناطق متشابهة جدًا', (string) $summary->similarLocalPagePairs)
                ->icon(Heroicon::OutlinedMapPin)
                ->color($summary->similarLocalPagePairs > 0 ? 'warning' : 'success'),
        ];
    }
}
