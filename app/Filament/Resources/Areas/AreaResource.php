<?php

namespace App\Filament\Resources\Areas;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Filament\Resources\Areas\Pages\CreateArea;
use App\Filament\Resources\Areas\Pages\EditArea;
use App\Filament\Resources\Areas\Pages\ListAreas;
use App\Models\Area;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class AreaResource extends Resource
{
    protected static ?string $model = Area::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'المحتوى';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'منطقة';

    protected static ?string $pluralModelLabel = 'المناطق';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('area')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('بيانات المنطقة')
                        ->icon('heroicon-o-map-pin')
                        ->schema([
                            Section::make()
                                ->schema([
                                    TextInput::make('name')->label('الاسم')->required()->maxLength(255),
                                    Select::make('area_group_id')
                                        ->label('مجموعة المناطق')
                                        ->relationship('group', 'name')
                                        ->searchable()
                                        ->preload(),
                                    TextInput::make('slug')
                                        ->label('الرابط الداخلي (Slug)')
                                        ->required()
                                        ->unique(ignoreRecord: true)
                                        ->helperText('مُعرّف داخلي للمنطقة. لا يعني وجوده أن للمنطقة صفحة منشورة.')
                                        ->maxLength(255),
                                    TextInput::make('sort_order')->label('ترتيب العرض')->numeric()->default(0),
                                ])
                                ->columns(2),
                        ]),

                    Tab::make('الصفحة والنشر')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Placeholder::make('area_page_note')
                                ->label('')
                                ->content('إنشاء منطقة لا ينشئ صفحة SEO تلقائيًا. أنشئ صفحة هنا فقط عند وجود محتوى ومشاريع حقيقية تستحق صفحة مستقلة لهذه المنطقة.'),
                            Section::make('صفحة المنطقة (اختياري)')
                                ->relationship('page')
                                ->schema([
                                    Hidden::make('type')->default(PageType::Area->value),
                                    TextInput::make('title')
                                        ->label('عنوان الصفحة')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn (Get $get, callable $set, ?string $state, ?string $old) => $get('slug') === Str::slug($old ?? '')
                                            ? $set('slug', Str::slug($state))
                                            : null)
                                        ->maxLength(255),
                                    TextInput::make('slug')
                                        ->label('رابط الصفحة (Slug)')
                                        ->unique(table: 'pages', ignoreRecord: true)
                                        ->maxLength(255),
                                    Select::make('status')
                                        ->label('الحالة')
                                        ->options([
                                            PageStatus::Draft->value => 'مسودة',
                                            PageStatus::Review->value => 'قيد المراجعة',
                                            PageStatus::Published->value => 'منشورة',
                                            PageStatus::Archived->value => 'مؤرشفة',
                                        ])
                                        ->default(PageStatus::Draft->value)
                                        ->live(),
                                    DateTimePicker::make('published_at')
                                        ->label('تاريخ النشر')
                                        ->visible(fn (Get $get) => $get('status') === PageStatus::Published->value),
                                    Section::make('SEO')
                                        ->relationship('seoMetadata')
                                        ->schema([
                                            TextInput::make('meta_title')->label('عنوان SEO'),
                                            Textarea::make('meta_description')->label('الوصف التعريفي')->rows(2),
                                            Toggle::make('robots_index')->label('السماح بالفهرسة')->default(true),
                                        ])
                                        ->columns(2)
                                        ->columnSpanFull(),
                                ])
                                ->columns(2),
                        ]),

                    Tab::make('الخدمات المتوفرة')
                        ->icon('heroicon-o-sparkles')
                        ->schema([
                            CheckboxList::make('services')
                                ->label('الخدمات المتوفرة في هذه المنطقة')
                                ->relationship('services', 'name')
                                ->searchable()
                                ->columns(3)
                                ->bulkToggleable(),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('الاسم')->searchable()->weight('medium'),
                TextColumn::make('group.name')->label('مجموعة المناطق')->badge(),
                IconColumn::make('page.id')->label('لها صفحة')->boolean()->getStateUsing(fn (Area $record) => $record->page !== null),
                TextColumn::make('services_count')->label('عدد الخدمات')->counts('services'),
                TextColumn::make('projects_count')->label('عدد المشاريع')->counts('projects'),
                TextColumn::make('sort_order')->label('الترتيب')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('area_group_id')->label('مجموعة المناطق')->relationship('group', 'name'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make()->authorizeIndividualRecords(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAreas::route('/'),
            'create' => CreateArea::route('/create'),
            'edit' => EditArea::route('/{record}/edit'),
        ];
    }
}
