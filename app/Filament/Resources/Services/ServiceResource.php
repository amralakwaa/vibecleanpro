<?php

namespace App\Filament\Resources\Services;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\EditService;
use App\Filament\Resources\Services\Pages\ListServices;
use App\Filament\Support\MediaPicker;
use App\Models\Service;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
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
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'المحتوى';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'خدمة';

    protected static ?string $pluralModelLabel = 'الخدمات';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('service')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('بيانات الخدمة')
                        ->icon('heroicon-o-sparkles')
                        ->schema([
                            Section::make()
                                ->schema([
                                    TextInput::make('name')
                                        ->label('الاسم')
                                        ->required()
                                        ->maxLength(255),
                                    Select::make('service_category_id')
                                        ->label('التصنيف')
                                        ->relationship('category', 'name')
                                        ->searchable()
                                        ->preload(),
                                    Textarea::make('short_description')
                                        ->label('وصف مختصر')
                                        ->helperText('يظهر في بطاقات الخدمة بالقوائم، وليس محتوى الصفحة الكامل.')
                                        ->rows(3)
                                        ->columnSpanFull(),
                                    TextInput::make('icon')
                                        ->label('الأيقونة (اسم Heroicon)')
                                        ->placeholder('heroicon-o-sparkles'),
                                    MediaPicker::make('featured_media_id', 'الصورة الرئيسية'),
                                    Toggle::make('is_featured')->label('خدمة مميزة'),
                                    TextInput::make('sort_order')->label('ترتيب العرض')->numeric()->default(0),
                                ])
                                ->columns(2),
                        ]),

                    Tab::make('الصفحة والنشر')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Section::make('صفحة الخدمة')
                                ->relationship('page')
                                ->description('هذه بيانات النشر والـSEO الخاصة بصفحة هذه الخدمة على الموقع.')
                                ->schema([
                                    Hidden::make('type')->default(PageType::Service->value),
                                    TextInput::make('title')
                                        ->label('عنوان الصفحة')
                                        ->required()
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn (Get $get, callable $set, ?string $state, ?string $old) => $get('slug') === Str::slug($old ?? '')
                                            ? $set('slug', Str::slug($state))
                                            : null)
                                        ->maxLength(255),
                                    TextInput::make('slug')
                                        ->label('الرابط (Slug)')
                                        ->required()
                                        ->unique(table: 'pages', ignoreRecord: true)
                                        ->maxLength(255),
                                    Select::make('status')
                                        ->label('الحالة')
                                        ->options([
                                            PageStatus::Draft->value => 'مسودة',
                                            PageStatus::Published->value => 'منشورة',
                                            PageStatus::Archived->value => 'مؤرشفة',
                                        ])
                                        ->default(PageStatus::Draft->value)
                                        ->required()
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

                    Tab::make('المناطق المتاحة')
                        ->icon('heroicon-o-map-pin')
                        ->schema([
                            CheckboxList::make('areas')
                                ->label('المناطق التي تتوفر بها هذه الخدمة')
                                ->relationship('areas', 'name')
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
                ImageColumn::make('featuredMedia.path')->label('')->circular()->defaultImageUrl(url('/images/placeholder.svg')),
                TextColumn::make('name')->label('الاسم')->searchable()->weight('medium'),
                TextColumn::make('category.name')->label('التصنيف')->badge(),
                TextColumn::make('page.status')->label('حالة الصفحة')->badge()->formatStateUsing(fn ($state) => match ($state?->value) {
                    'draft' => 'مسودة',
                    'published' => 'منشورة',
                    'archived' => 'مؤرشفة',
                    default => 'بدون صفحة',
                })->color(fn ($state) => match ($state?->value) {
                    'published' => 'success',
                    'draft' => 'gray',
                    'archived' => 'warning',
                    default => 'gray',
                }),
                IconColumn::make('is_featured')->label('مميزة')->boolean(),
                TextColumn::make('areas_count')->label('عدد المناطق')->counts('areas'),
                TextColumn::make('sort_order')->label('الترتيب')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('service_category_id')->label('التصنيف')->relationship('category', 'name'),
                TernaryFilter::make('is_featured')->label('مميزة'),
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
            'index' => ListServices::route('/'),
            'create' => CreateService::route('/create'),
            'edit' => EditService::route('/{record}/edit'),
        ];
    }
}
