<?php

namespace App\Filament\Widgets;

use App\Enums\MediaStatus;
use App\Enums\ServiceCapability;
use App\Models\Media;
use App\Models\Project;
use App\Models\Service;
use App\Models\Testimonial;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

/**
 * What still blocks publishing, in one place: photos awaiting review,
 * projects awaiting the owner's confirmation, services awaiting a
 * capability decision, reviews awaiting approval.
 */
class LaunchReadinessWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected ?string $heading = 'جاهزية الإطلاق — ما ينتظر قرارًا';

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user && ($user->can('approve_media') || $user->can('confirm_project') || $user->can('manage_business_profile'));
    }

    protected function getStats(): array
    {
        $media = Media::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return [
            Stat::make('صور جاهزة / بانتظار المراجعة / محجوبة', sprintf(
                '%d / %d / %d',
                $media[MediaStatus::Ready->value] ?? 0,
                $media[MediaStatus::Pending->value] ?? 0,
                $media[MediaStatus::Private->value] ?? 0,
            ))->icon(Heroicon::OutlinedPhoto)->color(($media[MediaStatus::Pending->value] ?? 0) > 0 ? 'warning' : 'success'),
            Stat::make('مشاريع بانتظار تأكيد المالك', (string) Project::query()->whereNull('owner_confirmed_at')->count())
                ->description(Project::query()->whereNotNull('owner_confirmed_at')->count().' مؤكَّدة')
                ->icon(Heroicon::OutlinedBriefcase)
                ->color('warning'),
            Stat::make('خدمات بانتظار تأكيد القدرة', (string) Service::query()->where('capability_status', ServiceCapability::NeedsConfirmation)->count())
                ->description('لا تُنشر صفحتها قبل التأكيد')
                ->icon(Heroicon::OutlinedQuestionMarkCircle)
                ->color('warning'),
            Stat::make('آراء عملاء بانتظار الاعتماد', (string) Testimonial::query()->whereNull('approved_at')->count())
                ->icon(Heroicon::OutlinedChatBubbleBottomCenterText),
        ];
    }
}
