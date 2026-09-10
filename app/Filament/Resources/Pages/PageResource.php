<?php

namespace App\Filament\Resources\Pages;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Support\ContentBlocks;
use App\Filament\Support\MediaPicker;
use App\Models\Page;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
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

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'المحتوى';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'صفحة';

    protected static ?string $pluralModelLabel = 'الصفحات';

    /**
     * Only standalone page types can be created here. A service/area/
     * project/article/offer page is always created together with its
     * entity, from that entity's own resource - never from a bare Page
     * record with no business data behind it.
     */
    private const STANDALONE_TYPES = [
        PageType::Trust,
        PageType::Legal,
        PageType::Landing,
    ];

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('بيانات الصفحة')
                ->schema([
                    Select::make('type')
                        ->label('نوع الصفحة')
                        ->options(fn (string $operation) => $operation === 'create'
                            ? collect(self::STANDALONE_TYPES)->mapWithKeys(fn (PageType $t) => [$t->value => self::typeLabel($t)])
                            : collect(PageType::cases())->mapWithKeys(fn (PageType $t) => [$t->value => self::typeLabel($t)]))
                        ->required()
                        ->disabled(fn (string $operation) => $operation === 'edit')
                        ->dehydrated()
                        ->helperText(fn (string $operation) => $operation === 'create'
                            ? 'صفحة خدمة/منطقة/مشروع/مقال/عرض تُنشأ من داخل ذلك المورد نفسه، وليس من هنا.'
                            : null),
                    Placeholder::make('pageable_info')
                        ->label('مرتبطة بـ')
                        ->content(fn (?Page $record) => $record?->pageable
                            ? ($record->pageable->name ?? $record->pageable->title ?? '#'.$record->pageable->id)
                            : 'صفحة مستقلة (غير مرتبطة بكيان)')
                        ->visible(fn (?Page $record) => $record !== null),
                    TextInput::make('title')
                        ->label('العنوان')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, callable $set, ?string $state, ?string $old) => $get('slug') === Str::slug($old ?? '')
                            ? $set('slug', Str::slug($state))
                            : null)
                        ->maxLength(255),
                    TextInput::make('slug')
                        ->label('الرابط (Slug)')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255)
                        ->helperText('تغيير الرابط بعد النشر يُفقد الزوار ومحركات البحث الرابط القديم إن لم يُضف Redirect.'),
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
                ])
                ->columns(2),

            Tabs::make('page_details')
                ->tabs([
                    Tab::make('SEO')
                        ->icon('heroicon-o-magnifying-glass')
                        ->schema([
                            Section::make()
                                ->relationship('seoMetadata')
                                ->schema([
                                    TextInput::make('meta_title')
                                        ->label('عنوان SEO')
                                        ->live(onBlur: true)
                                        ->hint(fn (?string $state) => strlen((string) $state).' حرف (لا يوجد طول مضمون من Google)')
                                        ->maxLength(255),
                                    Textarea::make('meta_description')
                                        ->label('الوصف التعريفي (Meta Description)')
                                        ->rows(3)
                                        ->live(onBlur: true)
                                        ->hint(fn (?string $state) => strlen((string) $state).' حرف (لا يوجد طول مضمون من Google)'),
                                    TextInput::make('canonical_url')
                                        ->label('الرابط الأساسي (Canonical)')
                                        ->url()
                                        ->helperText('اتركه فارغًا لاستخدام رابط الصفحة نفسها.'),
                                    Toggle::make('robots_index')
                                        ->label('السماح للفهرسة (Index)')
                                        ->default(true),
                                    Toggle::make('robots_follow')
                                        ->label('السماح بمتابعة الروابط (Follow)')
                                        ->default(true),
                                    TextInput::make('og_title')->label('عنوان مشاركة (OG Title)'),
                                    Textarea::make('og_description')->label('وصف مشاركة (OG Description)')->rows(2),
                                    MediaPicker::make('og_image_media_id', 'صورة المشاركة (OG Image)'),
                                ])
                                ->columns(2),
                        ]),

                    Tab::make('أقسام الصفحة')
                        ->icon('heroicon-o-squares-2x2')
                        ->schema([
                            ContentBlocks::field('content_blocks_builder'),
                        ]),

                    Tab::make('الأسئلة الشائعة')
                        ->icon('heroicon-o-question-mark-circle')
                        ->schema([
                            Repeater::make('faqs')
                                ->label('الأسئلة')
                                ->relationship()
                                ->schema([
                                    TextInput::make('question')->label('السؤال')->required(),
                                    Textarea::make('answer')->label('الإجابة')->rows(2)->required(),
                                    Toggle::make('is_active')->label('مفعّل')->default(true),
                                ])
                                ->itemLabel(fn (array $state) => $state['question'] ?? null)
                                ->collapsible()
                                ->orderColumn('sort_order')
                                ->defaultItems(0),
                        ]),
                ])
                ->visible(fn (string $operation) => $operation === 'edit')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('العنوان')->searchable()->weight('medium'),
                TextColumn::make('type')->label('النوع')->badge()->formatStateUsing(fn (PageType $state) => self::typeLabel($state)),
                TextColumn::make('slug')->label('الرابط')->searchable()->copyable(),
                TextColumn::make('status')->label('الحالة')->badge()
                    ->formatStateUsing(fn (PageStatus $state) => match ($state) {
                        PageStatus::Draft => 'مسودة',
                        PageStatus::Published => 'منشورة',
                        PageStatus::Archived => 'مؤرشفة',
                    })
                    ->color(fn (PageStatus $state) => match ($state) {
                        PageStatus::Draft => 'gray',
                        PageStatus::Published => 'success',
                        PageStatus::Archived => 'warning',
                    }),
                IconColumn::make('pageable_type')->label('مرتبطة بكيان')->boolean()->getStateUsing(fn (Page $record) => $record->pageable_type !== null),
                TextColumn::make('published_at')->label('تاريخ النشر')->dateTime('Y-m-d')->sortable(),
                TextColumn::make('updated_at')->label('آخر تعديل')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->label('النوع')
                    ->options(collect(PageType::cases())->mapWithKeys(fn (PageType $t) => [$t->value => self::typeLabel($t)])),
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        PageStatus::Draft->value => 'مسودة',
                        PageStatus::Published->value => 'منشورة',
                        PageStatus::Archived->value => 'مؤرشفة',
                    ]),
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
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }

    public static function typeLabel(PageType $type): string
    {
        return match ($type) {
            PageType::Service => 'خدمة',
            PageType::Area => 'منطقة',
            PageType::Project => 'مشروع',
            PageType::Article => 'مقال',
            PageType::Offer => 'عرض',
            PageType::Trust => 'صفحة ثقة',
            PageType::Legal => 'صفحة قانونية',
            PageType::Landing => 'صفحة هبوط',
        };
    }
}
