<?php

namespace App\Filament\Resources\Media\Pages;

use App\Filament\Resources\Media\MediaResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Livewire\Attributes\Url;

class ListMedia extends ListRecords
{
    protected static string $resource = MediaResource::class;

    /**
     * Sidra's media library opens as a thumbnail grid with a list toggle
     * beside the filters. Both views earn their place here and neither can
     * replace the other: a photo library is browsed by eye, so the grid is
     * the right default, but the grid cannot show alt text, status or size,
     * and the panel's largest open task is describing the images that have
     * no alt text yet. Forcing one view would make that job harder.
     *
     * #[Url] keeps the choice in the query string, so a grid view survives a
     * reload and can be linked to or bookmarked.
     */
    #[Url]
    public bool $asGrid = true;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggleView')
                ->label($this->asGrid ? 'عرض كجدول' : 'عرض كشبكة')
                ->icon($this->asGrid ? Heroicon::OutlinedBars3 : Heroicon::OutlinedSquares2x2)
                ->color('gray')
                ->action(fn () => $this->asGrid = ! $this->asGrid),
            CreateAction::make(),
        ];
    }

    public function table(Table $table): Table
    {
        $table = parent::table($table);

        if (! $this->asGrid) {
            return $table;
        }

        // In grid mode the row becomes one card: the image, the filename, and
        // the one fact that decides whether the image is finished - whether
        // it has alt text. Everything else the table shows needs a column
        // header to be readable, which a card has no room for.
        return $table
            ->columns([
                Stack::make([
                    ImageColumn::make('path')
                        ->label('')
                        ->disk('public')
                        ->height(160)
                        ->extraImgAttributes(['class' => 'w-full rounded-lg object-cover']),
                    TextColumn::make('original_filename')
                        ->label('')
                        ->searchable()
                        ->size(TextSize::ExtraSmall)
                        ->limit(34),
                    TextColumn::make('alt_text')
                        ->label('')
                        ->searchable()
                        ->size(TextSize::ExtraSmall)
                        ->limit(40)
                        ->placeholder('بلا نص بديل')
                        ->color(fn (?string $state): string => blank($state) ? 'danger' : 'gray'),
                ])->space(2),
            ])
            ->contentGrid(['sm' => 2, 'md' => 3, 'xl' => 5])
            ->paginationPageOptions([24, 48, 96]);
    }
}
