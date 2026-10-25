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

    protected static ?int $sort = 7;

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

        // Sidra's brand blue stepping into its gold, read as one family
        // rather than as a bag of unrelated hues.
        //
        // Every step is LIGHTER than the card it sits on. The first version
        // of this palette started at the brand navy #071228 and was built
        // for the old light theme; against Sidra's #161b22 card the dark end
        // of it disappeared entirely and the doughnut read as three slices
        // instead of eight.
        $palette = ['#4d96c8', '#2878b4', '#7eb4da', '#1b4f72', '#d4ac0d', '#e8c132', '#f5d87a', '#b1bac4', '#6e7681'];

        return [
            'datasets' => [[
                'data' => $counts->values()->all(),
                'backgroundColor' => array_slice($palette, 0, $counts->count()),
                // The card colour, not white: the gap between slices has to
                // read as the card showing through, not as a bright ring.
                'borderColor' => '#161b22',
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
                    'labels' => ['boxWidth' => 12, 'padding' => 14, 'font' => ['size' => 12], 'color' => '#b1bac4'],
                ],
            ],
        ];
    }
}
