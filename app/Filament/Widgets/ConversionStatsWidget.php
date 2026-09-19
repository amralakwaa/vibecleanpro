<?php

namespace App\Filament\Widgets;

use App\Enums\ConversionEventType;
use App\Support\Tracking\ConversionReport;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class ConversionStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'التحويلات — آخر 30 يومًا';

    public static function canView(): bool
    {
        return (bool) Auth::user()?->can('view_conversion_reports');
    }

    protected function getStats(): array
    {
        $report = new ConversionReport(30);
        $totals = $report->totals();
        $leads = $report->leadCount();
        $won = $report->wonCount();

        return [
            Stat::make('نقرات واتساب', (string) $totals[ConversionEventType::WhatsappClick->value])
                ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                ->color('success'),
            Stat::make('نقرات الاتصال', (string) $totals[ConversionEventType::PhoneClick->value])
                ->icon(Heroicon::OutlinedPhone),
            Stat::make('طلبات عرض السعر', (string) $totals[ConversionEventType::QuoteFormSubmit->value])
                ->description($totals[ConversionEventType::QuoteFormStart->value].' بدأوا تعبئة النموذج')
                ->icon(Heroicon::OutlinedDocumentText),
            Stat::make('رسائل التواصل', (string) $totals[ConversionEventType::ContactFormSubmit->value])
                ->icon(Heroicon::OutlinedEnvelope),
            Stat::make('العملاء المحتملون', (string) $leads)
                ->description($leads > 0 ? sprintf('%d قبلوا العرض أو اكتملت خدمتهم (%d%%)', $won, round($won / $leads * 100)) : 'لا توجد طلبات بعد')
                ->icon(Heroicon::OutlinedInbox)
                ->color('info'),
        ];
    }
}
