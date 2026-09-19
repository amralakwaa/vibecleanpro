<?php

namespace App\Filament\Widgets;

use App\Support\Tracking\ConversionReport;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class ConversionBreakdownWidget extends Widget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.conversion-breakdown';

    public static function canView(): bool
    {
        return (bool) Auth::user()?->can('view_conversion_reports');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $report = new ConversionReport(30);

        return [
            'days' => $report->days(),
            'lists' => [
                'أكثر الخدمات تحويلًا' => $report->topServices(),
                'أكثر الأحياء تحويلًا' => $report->topAreas(),
                'مصادر التحويل' => $report->bySource(),
            ],
        ];
    }
}
