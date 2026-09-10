<?php

namespace App\Filament\Widgets;

use App\Enums\LeadStatus;
use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Article;
use App\Models\Lead;
use App\Models\Page;
use App\Models\Project;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class DashboardStats extends BaseWidget
{
    protected function getStats(): array
    {
        $stats = [];

        if (Auth::user()?->can('view_any_lead')) {
            $stats[] = Stat::make('Leads جديدة', (string) Lead::query()->where('status', LeadStatus::New)->count())
                ->icon(Heroicon::OutlinedInbox)
                ->color('info');

            $stats[] = Stat::make('Leads هذا الشهر', (string) Lead::query()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count())
                ->icon(Heroicon::OutlinedCalendar);
        }

        if (Auth::user()?->can('view_any_service')) {
            $stats[] = Stat::make('خدمات منشورة', (string) Page::query()->where('type', PageType::Service)->where('status', PageStatus::Published)->count())
                ->icon(Heroicon::OutlinedSparkles)
                ->color('success');
        }

        if (Auth::user()?->can('view_any_area')) {
            $stats[] = Stat::make('مناطق منشورة', (string) Page::query()->where('type', PageType::Area)->where('status', PageStatus::Published)->count())
                ->icon(Heroicon::OutlinedMapPin)
                ->color('success');
        }

        if (Auth::user()?->can('view_any_project')) {
            $stats[] = Stat::make('إجمالي المشاريع', (string) Project::query()->count())
                ->icon(Heroicon::OutlinedBriefcase);
        }

        if (Auth::user()?->can('view_any_article')) {
            $stats[] = Stat::make('إجمالي المقالات', (string) Article::query()->count())
                ->icon(Heroicon::OutlinedNewspaper);
        }

        if (Auth::user()?->can('view_any_page')) {
            $stats[] = Stat::make('مسودة / مراجعة / منشورة', sprintf(
                '%d / %d / %d',
                Page::query()->where('status', PageStatus::Draft)->count(),
                Page::query()->where('status', PageStatus::Review)->count(),
                Page::query()->where('status', PageStatus::Published)->count(),
            ))->icon(Heroicon::OutlinedDocumentText)->color('warning');
        }

        return $stats;
    }

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user && ($user->can('view_any_lead') || $user->can('view_any_page'));
    }
}
