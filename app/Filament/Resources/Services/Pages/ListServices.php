<?php

namespace App\Filament\Resources\Services\Pages;

use App\Filament\Resources\Services\ServiceResource;
use App\Models\Service;
use App\Models\ServiceCategory;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListServices extends ListRecords
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * Category tabs, the way Sidra's service list works.
     *
     * The category filter already existed in the dropdown, but a filter has
     * to be opened, read and chosen before it says anything, while a tab bar
     * shows the shape of the catalogue at rest. Each tab carries its count,
     * and a category with no services gets no tab - an empty tab is a dead
     * end that still costs width.
     *
     * The last tab is بلا مشاريع, which no dropdown offers: it collects the
     * services whose pages have no documented project behind them. That is
     * the same list ServiceEvidenceChart puts at the top of the dashboard,
     * reachable here as one click instead of as a chart to interpret.
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $counts = Service::query()
            ->selectRaw('service_category_id, count(*) as total')
            ->groupBy('service_category_id')
            ->pluck('total', 'service_category_id');

        $tabs = [
            'all' => Tab::make('الكل')->badge(Service::query()->count()),
        ];

        $categories = ServiceCategory::query()
            ->whereIn('id', $counts->keys()->filter())
            ->orderBy('sort_order')
            ->get();

        foreach ($categories as $category) {
            $tabs['category-'.$category->id] = Tab::make($category->name)
                ->badge((int) $counts[$category->id])
                ->modifyQueryUsing(fn (Builder $query) => $query->where('service_category_id', $category->id));
        }

        $withoutEvidence = Service::query()->doesntHave('projects')->count();

        $tabs['no-projects'] = Tab::make('بلا مشاريع')
            ->badge($withoutEvidence)
            ->badgeColor($withoutEvidence > 0 ? 'danger' : 'gray')
            ->modifyQueryUsing(fn (Builder $query) => $query->doesntHave('projects'));

        return $tabs;
    }
}
