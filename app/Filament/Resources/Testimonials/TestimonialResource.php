<?php

namespace App\Filament\Resources\Testimonials;

use App\Enums\TestimonialSource;
use App\Filament\Resources\Testimonials\Pages\CreateTestimonial;
use App\Filament\Resources\Testimonials\Pages\EditTestimonial;
use App\Filament\Resources\Testimonials\Pages\ListTestimonials;
use App\Models\Testimonial;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use UnitEnum;

class TestimonialResource extends Resource
{
    protected static ?string $model = Testimonial::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    protected static string|UnitEnum|null $navigationGroup = 'الوسائط';

    protected static ?string $recordTitleAttribute = 'author_name';

    protected static ?string $modelLabel = 'رأي عميل';

    protected static ?string $pluralModelLabel = 'آراء العملاء';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('author_name')->label('اسم العميل')->required()->maxLength(255),
            Select::make('rating')
                ->label('التقييم')
                ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5'])
                ->default(5),
            Select::make('area_id')->label('المنطقة')->relationship('area', 'name')->searchable()->preload(),
            Select::make('service_id')->label('الخدمة')->relationship('service', 'name')->searchable()->preload(),
            Textarea::make('content')->label('نص الرأي')->required()->rows(4)->columnSpanFull(),
            Toggle::make('is_featured')->label('مميز')->default(false),
            TextInput::make('sort_order')->label('ترتيب العرض')->numeric()->default(0),
            Section::make('المصدر والاعتماد')
                ->description('لا يظهر الرأي في الموقع قبل اعتماده. تعديل نص الرأي بعد الاعتماد يلغي الاعتماد تلقائيًا.')
                ->schema([
                    Select::make('source')->label('المصدر')->options(TestimonialSource::options()),
                    TextInput::make('source_ref')->label('رابط أو مرجع المصدر')->maxLength(255),
                    Toggle::make('consent_confirmed')->label('وافق العميل على نشر رأيه'),
                    DateTimePicker::make('approved_at')
                        ->label('تاريخ الاعتماد')
                        ->disabled(fn (): bool => ! (auth()->user()?->can('approve_testimonial') ?? false)),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('author_name')->label('العميل')->searchable(),
                TextColumn::make('rating')->label('التقييم')->badge(),
                TextColumn::make('content')->label('الرأي')->limit(50),
                TextColumn::make('service.name')->label('الخدمة'),
                TextColumn::make('area.name')->label('المنطقة'),
                IconColumn::make('is_featured')->label('مميز')->boolean(),
                IconColumn::make('approved_at')->label('معتمد')->boolean()->getStateUsing(fn (Testimonial $record) => $record->approved_at !== null),
            ])
            ->defaultSort('sort_order')
            ->filters([
                TernaryFilter::make('approved_at')->label('الاعتماد')->nullable(),
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
            'index' => ListTestimonials::route('/'),
            'create' => CreateTestimonial::route('/create'),
            'edit' => EditTestimonial::route('/{record}/edit'),
        ];
    }
}
