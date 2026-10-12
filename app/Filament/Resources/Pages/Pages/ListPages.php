<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Filament\Resources\Pages\PageResource;
use App\Models\Page;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPages extends ListRecords
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * Type tabs above the table, the way Sidra's service list works.
     *
     * 122 rows across nine types is past what a dropdown filter serves well:
     * a filter has to be opened, read and chosen before it tells you
     * anything, while a tab bar shows the whole shape of the library at rest
     * and makes "just the articles" one click.
     *
     * Each tab carries its own count badge, so the row that needs attention
     * is visible before anything is clicked. Only types that actually have
     * rows get a tab - an empty tab is a dead end that still costs width.
     *
     * The first tab is الكل rather than a type, because the unfiltered table
     * is what someone arriving from the sidebar expects to land on.
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $counts = Page::query()
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $tabs = [
            'all' => Tab::make('الكل')->badge($counts->sum()),
        ];

        foreach (PageType::cases() as $type) {
            $count = (int) ($counts[$type->value] ?? 0);

            if ($count === 0) {
                continue;
            }

            $tabs[$type->value] = Tab::make(PageResource::typeLabel($type))
                ->badge($count)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', $type));
        }

        // Drafts and pages in review are the owner's own queue, so they get a
        // tab of their own rather than living behind the status filter. It is
        // tinted only while it has rows: a permanently amber tab reading zero
        // trains people to ignore the colour.
        $waiting = Page::query()
            ->whereIn('status', [PageStatus::Draft, PageStatus::Review])
            ->count();

        $tabs['waiting'] = Tab::make('بانتظارك')
            ->badge($waiting)
            ->badgeColor($waiting > 0 ? 'warning' : 'gray')
            ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [PageStatus::Draft, PageStatus::Review]));

        return $tabs;
    }
}
