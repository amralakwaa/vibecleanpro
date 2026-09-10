<?php

namespace App\Filament\Resources\Articles;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\Articles\Pages\ListArticles;
use App\Filament\Support\MediaPicker;
use App\Models\Article;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|UnitEnum|null $navigationGroup = 'المحتوى';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $modelLabel = 'مقال';

    protected static ?string $pluralModelLabel = 'المقالات';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('article')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('بيانات المقال')
                        ->icon('heroicon-o-newspaper')
                        ->schema([
                            Section::make()
                                ->schema([
                                    TextInput::make('title')->label('العنوان')->required()->maxLength(255),
                                    Select::make('article_category_id')
                                        ->label('التصنيف')
                                        ->relationship('category', 'name')
                                        ->searchable()
                                        ->preload(),
                                    Select::make('author_id')
                                        ->label('الكاتب')
                                        ->relationship('author', 'name')
                                        ->searchable()
                                        ->preload(),
                                    MediaPicker::make('featured_media_id', 'الصورة البارزة'),
                                    Textarea::make('excerpt')->label('مقتطف')->rows(3)->columnSpanFull(),
                                ])
                                ->columns(2),
                            CheckboxList::make('services')
                                ->label('الخدمات ذات الصلة')
                                ->relationship('services', 'name')
                                ->searchable()
                                ->columns(3)
                                ->bulkToggleable(),
                            CheckboxList::make('areas')
                                ->label('المناطق ذات الصلة')
                                ->relationship('areas', 'name')
                                ->searchable()
                                ->columns(3)
                                ->bulkToggleable(),
                        ]),

                    Tab::make('الصفحة والنشر')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Section::make('صفحة المقال')
                                ->relationship('page')
                                ->schema([
                                    Hidden::make('type')->default(PageType::Article->value),
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
                TextColumn::make('category.name')->label('التصنيف')->badge(),
                TextColumn::make('author.name')->label('الكاتب'),
                TextColumn::make('page.status')->label('حالة الصفحة')->badge()->formatStateUsing(fn ($state) => match ($state?->value) {
                    'draft' => 'مسودة',
                    'published' => 'منشورة',
                    'archived' => 'مؤرشفة',
                    default => 'بدون صفحة',
                })->color(fn ($state) => match ($state?->value) {
                    'published' => 'success',
                    default => 'gray',
                }),
                TextColumn::make('page.published_at')->label('تاريخ النشر')->dateTime('Y-m-d')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('article_category_id')->label('التصنيف')->relationship('category', 'name'),
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
            'index' => ListArticles::route('/'),
            'create' => CreateArticle::route('/create'),
            'edit' => EditArticle::route('/{record}/edit'),
        ];
    }
}
