<?php

namespace App\Filament\Widgets;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Filament\Resources\Pages\PageResource;
use App\Models\Page;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

/**
 * The published site, by kind of page.
 *
 * It answers a question the stat cards cannot: what is this site actually
 * made of. A library that is four fifths project pages and one fifth
 * everything else is a portfolio; one with no projects behind its services
 * is a brochure. The shape is the point, which is why this is a doughnut
 * and not five more numbers.
 */
class ContentMixChart extends ChartWidget
{
    protected ?string $heading = 'تركيبة المحتوى المنشور';

    protected ?string $description = 'ما يتكوّن منه الموقع فعلاً — الصفحات المنشورة فقط.';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '320px';

    public static function canView(): bool
    {
        return (bool) Auth::user()?->can('view_any_page');
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $counts = Page::query()
            ->where('status', PageStatus::Published)
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type')
            ->sortDesc();

        // Brand navy stepping down to brand blue: one family, read as one
        // whole rather than as a bag of unrelated hues.
        $palette = ['#071228', '#16233d', '#243350', '#2563eb', '#3b7cf6', '#609afa', '#93bbfd', '#bfd7fe', '#dbe8fe'];

        return [
            'datasets' => [[
                'data' => $counts->values()->all(),
                'backgroundColor' => array_slice($palette, 0, $counts->count()),
                'borderColor' => '#ffffff',
                'borderWidth' => 2,
            ]],
            // PageResource::typeLabel is the one place these are named in
            // Arabic, so it is reused rather than duplicated. Its longer
            // entries carry a parenthetical aimed at an editor filling a
            // form ("صفحة قانونية (سياسة الخصوصية...)"); a legend chip has
            // no room for that, so the parenthetical is trimmed here and
            // nowhere else.
            'labels' => $counts->keys()
                ->map(fn (string $type): string => trim(preg_replace(
                    '/\s*\(.*\)$/u',
                    '',
                    PageResource::typeLabel(PageType::from($type)),
                )))
                ->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'cutout' => '58%',
            'plugins' => [
                'legend' => [
                    'position' => 'right',
                    'labels' => ['boxWidth' => 12, 'padding' => 14, 'font' => ['size' => 12]],
                ],
            ],
        ];
    }
}
