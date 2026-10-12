<?php

namespace App\Filament\Widgets;

use App\Enums\PageStatus;
use App\Models\Service;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

/**
 * Documented projects per published service.
 *
 * This is the one chart on the dashboard that changes what the owner does
 * next. The whole public proposition is "we document our work with photos",
 * and a service page with no project behind it has nothing to show - it is
 * the weakest page on the site and the easiest one to fix, because fixing it
 * means uploading photos of work already done rather than writing anything.
 *
 * Horizontal bars, sorted ascending so the bare services sit at the top of
 * the reading order in RTL and are the first thing the eye lands on. A
 * dashboard that buries the problem under the healthy rows is decoration.
 */
class ServiceEvidenceChart extends ChartWidget
{
    protected ?string $heading = 'المشاريع الموثّقة لكل خدمة';

    protected ?string $description = 'الخدمات بلا مشاريع في الأعلى — صفحتها بلا دليل مصوَّر، وهي أسرع ما يمكن تحسينه.';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '420px';

    public static function canView(): bool
    {
        return (bool) Auth::user()?->can('view_any_service');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $services = Service::query()
            ->whereHas('page', fn ($query) => $query->where('status', PageStatus::Published))
            ->withCount('projects')
            ->orderBy('projects_count')
            ->orderBy('name')
            ->get();

        // Red for nothing at all, amber for a thin page, brand blue once the
        // service is genuinely evidenced. Color carries the state here; it is
        // not a palette applied for looks, and the three steps match the
        // thresholds an editor would use by eye anyway.
        $colors = $services->map(fn (Service $service) => match (true) {
            $service->projects_count === 0 => 'rgba(220, 38, 38, 0.85)',
            $service->projects_count < 3 => 'rgba(217, 119, 6, 0.85)',
            default => 'rgba(37, 99, 235, 0.85)',
        });

        return [
            'datasets' => [[
                'label' => 'عدد المشاريع',
                'data' => $services->pluck('projects_count')->all(),
                'backgroundColor' => $colors->all(),
                'borderRadius' => 6,
                'borderSkipped' => false,
                'barThickness' => 'flex',
                'maxBarThickness' => 22,
            ]],
            'labels' => $services->pluck('name')->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    // Project counts are whole things; a "2.5 projects"
                    // gridline is noise.
                    'ticks' => ['precision' => 0, 'stepSize' => 1, 'color' => '#8b949e'],
                    'grid' => ['color' => 'rgba(255, 255, 255, 0.06)'],
                ],
                'y' => [
                    'grid' => ['display' => false],
                    'ticks' => ['autoSkip' => false, 'font' => ['size' => 12], 'color' => '#b1bac4'],
                ],
            ],
        ];
    }
}
