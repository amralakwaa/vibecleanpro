<?php

namespace App\Filament\Resources\InternalLinks;

use App\Filament\Resources\InternalLinks\Pages\CreateInternalLink;
use App\Filament\Resources\InternalLinks\Pages\EditInternalLink;
use App\Filament\Resources\InternalLinks\Pages\ListInternalLinks;
use App\Models\InternalLink;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class InternalLinkResource extends Resource
{
    protected static ?string $model = InternalLink::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    protected static string|UnitEnum|null $navigationGroup = 'SEO';

    protected static ?string $modelLabel = 'رابط داخلي';

    protected static ?string $pluralModelLabel = 'الروابط الداخلية';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('from_page_id')
                ->label('من صفحة')
                ->relationship('fromPage', 'title')
                ->searchable()
                ->preload()
                ->required(),
            Select::make('to_page_id')
                ->label('إلى صفحة')
                ->relationship('toPage', 'title')
                ->searchable()
                ->preload()
                ->required()
                ->different('from_page_id')
                ->helperText('يجب أن تختلف عن صفحة المصدر.'),
            TextInput::make('anchor_text')
                ->label('نص الرابط (Anchor Text)')
                ->maxLength(255)
                ->helperText('اتركه فارغًا لاستخدام عنوان الصفحة الهدف.'),
            TextInput::make('context')
                ->label('السياق')
                ->placeholder('related, body, footer...')
                ->maxLength(255),
            TextInput::make('sort_order')->label('الترتيب')->numeric()->default(0),
            Toggle::make('is_active')->label('مفعّل')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fromPage.title')->label('من')->searchable(),
                TextColumn::make('toPage.title')->label('إلى')->searchable(),
                TextColumn::make('anchor_text')->label('نص الرابط')->limit(30),
                TextColumn::make('context')->label('السياق')->badge(),
                IconColumn::make('is_active')->label('مفعّل')->boolean(),
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
            'index' => ListInternalLinks::route('/'),
            'create' => CreateInternalLink::route('/create'),
            'edit' => EditInternalLink::route('/{record}/edit'),
        ];
    }
}
