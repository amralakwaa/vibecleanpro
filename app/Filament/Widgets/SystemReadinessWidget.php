<?php

namespace App\Filament\Widgets;

use App\Support\Readiness\LaunchReadiness;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

/**
 * Operational readiness at a glance: what works, what is configured but
 * off, what is missing. States are computed by LaunchReadiness.
 */
class SystemReadinessWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'جاهزية التشغيل';

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user && ($user->can('manage_business_profile') || $user->can('manage_site_settings'));
    }

    protected function getStats(): array
    {
        $readiness = app(LaunchReadiness::class);
        $ok = ['ready', 'configured', 'active', 'published', 'visible', 'complete', 'recent'];

        $stat = fn (string $title, array $item, $icon) => Stat::make($title, $item['label'])
            ->description($item['detail'])
            ->icon($icon)
            ->color(in_array($item['state'], $ok, true) ? 'success' : ($item['state'] === 'configured_disabled' ? 'gray' : 'danger'));

        return [
            $stat('إشعارات الطلبات بالبريد', $readiness->leadNotifications(), Heroicon::OutlinedEnvelope),
            $stat('سياسة الخصوصية', $readiness->privacyPolicy(), Heroicon::OutlinedShieldCheck),
            $stat('Google Search Console', $readiness->searchConsole(), Heroicon::OutlinedMagnifyingGlass),
            $stat('Google Analytics 4', $readiness->ga4(), Heroicon::OutlinedChartBar),
            $stat('زر تقييم Google', $readiness->googleReviewCta(), Heroicon::OutlinedStar),
            $stat('التحقق الثنائي', $readiness->twoFactor(), Heroicon::OutlinedKey),
            $stat('النسخ الاحتياطي', $readiness->backups(), Heroicon::OutlinedServerStack),
        ];
    }
}
