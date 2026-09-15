<?php

namespace App\Filament\Resources\Offers;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Filament\Resources\Offers\Pages\CreateOffer;
use App\Filament\Resources\Offers\Pages\EditOffer;
use App\Filament\Resources\Offers\Pages\ListOffers;
use App\Filament\Support\MediaPicker;
use App\Models\Offer;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class OfferResource extends Resource
{
    protected static ?string $model = Offer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'المحتوى';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $modelLabel = 'عرض';

    protected static ?string $pluralModelLabel = 'العروض';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('offer')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('بيانات العرض')
                        ->icon('heroicon-o-tag')
                        ->schema([
                            Section::make()
                                ->schema([
                                    TextInput::make('title')->label('عنوان العرض')->required()->maxLength(255),
                                    TextInput::make('discount_label')
                                        ->label('نص الخصم')
                                        ->placeholder('مثال: خصم 20%')
                                        ->helperText('نص وصفي يُعرض للزائر. تفاصيل الشروط تُكتب داخل محتوى الصفحة.'),
                                    TextInput::make('offer_price')
                                        ->label('سعر العرض (اختياري)')
                                        ->numeric()
                                        ->minValue(1)
                                        ->suffix(config('pricing.symbol'))
                                        ->helperText('يُعرض كرقم فقط إن أُدخل. يظهر "بدلًا من" تلقائيًا عندما يغطي العرض خدمة واحدة لها سعر معلن أعلى منه.'),
                                    MediaPicker::make('featured_media_id', 'صورة العرض'),
                                    DatePicker::make('starts_at')->label('تاريخ البداية'),
                                    DatePicker::make('ends_at')->label('تاريخ الانتهاء'),
                                    Toggle::make('is_active')->label('مفعّل')->default(true),
                                    TextInput::make('sort_order')->label('ترتيب العرض')->numeric()->default(0),
                                ])
                                ->columns(2),
                            CheckboxList::make('services')
                                ->label('الخدمات المشمولة')
                                ->relationship('services', 'name')
                                ->searchable()
                                ->columns(3)
                                ->bulkToggleable(),
                            CheckboxList::make('areas')
                                ->label('المناطق المشمولة')
                                ->relationship('areas', 'name')
                                ->searchable()
                                ->columns(3)
                                ->bulkToggleable(),
                        ]),

                    Tab::make('الصفحة والنشر')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Section::make('صفحة العرض')
                                ->relationship('page')
                                ->schema([
                                    Hidden::make('type')->default(PageType::Offer->value),
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
                                            PageStatus::Review->value => 'قيد المراجعة',
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
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('العنوان')->searchable()->weight('medium'),
                TextColumn::make('discount_label')->label('الخصم'),
                TextColumn::make('starts_at')->label('البداية')->date('Y-m-d'),
                TextColumn::make('ends_at')->label('الانتهاء')->date('Y-m-d'),
                IconColumn::make('is_active')->label('مفعّل')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                TernaryFilter::make('is_active')->label('مفعّل'),
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
            'index' => ListOffers::route('/'),
            'create' => CreateOffer::route('/create'),
            'edit' => EditOffer::route('/{record}/edit'),
        ];
    }
}
