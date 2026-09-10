<?php

namespace App\Filament\Resources\AreaGroups;

use App\Filament\Resources\AreaGroups\Pages\CreateAreaGroup;
use App\Filament\Resources\AreaGroups\Pages\EditAreaGroup;
use App\Filament\Resources\AreaGroups\Pages\ListAreaGroups;
use App\Filament\Resources\Areas\AreaResource;
use App\Models\AreaGroup;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class AreaGroupResource extends Resource
{
    protected static ?string $model = AreaGroup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'المحتوى';

    protected static ?string $navigationParentItem = AreaResource::class;

    protected static ?string $modelLabel = 'مجموعة مناطق';

    protected static ?string $pluralModelLabel = 'مجموعات المناطق';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('الاسم')
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(function (string $operation, ?string $state, callable $set) {
                    if ($operation === 'create') {
                        $set('slug', Str::slug($state));
                    }
                })
                ->maxLength(255),
            TextInput::make('slug')
                ->label('الرابط (Slug)')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            TextInput::make('sort_order')
                ->label('ترتيب العرض')
                ->numeric()
                ->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('الاسم')->searchable(),
                TextColumn::make('slug')->label('الرابط')->searchable(),
                TextColumn::make('areas_count')->label('عدد المناطق')->counts('areas'),
                TextColumn::make('sort_order')->label('الترتيب')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAreaGroups::route('/'),
            'create' => CreateAreaGroup::route('/create'),
            'edit' => EditAreaGroup::route('/{record}/edit'),
        ];
    }
}
