<?php

namespace App\Filament\Resources\Projects;

use App\Enums\MediaStage;
use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Support\MediaPicker;
use App\Models\Project;
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

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|UnitEnum|null $navigationGroup = 'المحتوى';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $modelLabel = 'مشروع';

    protected static ?string $pluralModelLabel = 'المشاريع';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('project')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('بيانات المشروع')
                        ->icon('heroicon-o-briefcase')
                        ->schema([
                            Section::make()
                                ->schema([
                                    TextInput::make('title')->label('العنوان')->required()->maxLength(255),
                                    Select::make('area_id')
                                        ->label('المنطقة')
                                        ->relationship('area', 'name')
                                        ->searchable()
                                        ->preload(),
                                    Textarea::make('summary')->label('ملخص المشروع')->rows(3)->columnSpanFull(),
                                    DatePicker::make('completed_at')->label('تاريخ الإنجاز'),
                                    Toggle::make('is_featured')->label('مشروع مميز'),
                                    TextInput::make('sort_order')->label('ترتيب العرض')->numeric()->default(0),
                                ])
                                ->columns(2),
                            CheckboxList::make('services')
                                ->label('الخدمات المرتبطة بهذا المشروع')
                                ->relationship('services', 'name')
                                ->searchable()
                                ->columns(3)
                                ->bulkToggleable(),
                        ]),

                    Tab::make('الصور (قبل/أثناء/بعد)')
                        ->icon('heroicon-o-photo')
                        ->schema([
                            Repeater::make('projectMedia')
                                ->label('صور المشروع')
                                ->relationship()
                                ->schema([
                                    MediaPicker::make('media_id', 'الصورة')->required()->columnSpan(2),
                                    Select::make('stage')
                                        ->label('المرحلة')
                                        ->options([
                                            MediaStage::Before->value => 'قبل',
                                            MediaStage::During->value => 'أثناء',
                                            MediaStage::After->value => 'بعد',
                                        ])
                                        ->default(MediaStage::During->value)
                                        ->required(),
                                ])
                                ->columns(3)
                                ->orderColumn('sort_order')
                                ->reorderableWithButtons()
                                ->defaultItems(0)
                                ->addActionLabel('إضافة صورة')
                                ->helperText('صورة ألت وتعليق كل صورة يُداران من مكتبة الوسائط نفسها.'),
                        ]),

                    Tab::make('الصفحة والنشر')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Section::make('صفحة المشروع (اختياري)')
                                ->relationship('page')
                                ->schema([
                                    Hidden::make('type')->default(PageType::Project->value),
                                    TextInput::make('title')
                                        ->label('عنوان الصفحة')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn (Get $get, callable $set, ?string $state, ?string $old) => $get('slug') === Str::slug($old ?? '')
                                            ? $set('slug', Str::slug($state))
                                            : null)
                                        ->maxLength(255),
                                    TextInput::make('slug')
                                        ->label('الرابط (Slug)')
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
                TextColumn::make('area.name')->label('المنطقة')->badge(),
                TextColumn::make('media_count')->label('عدد الصور')->counts('media'),
                TextColumn::make('completed_at')->label('تاريخ الإنجاز')->date('Y-m-d')->sortable(),
                IconColumn::make('is_featured')->label('مميز')->boolean(),
                IconColumn::make('page.id')->label('له صفحة')->boolean()->getStateUsing(fn (Project $record) => $record->page !== null),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('area_id')->label('المنطقة')->relationship('area', 'name'),
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
            'index' => ListProjects::route('/'),
            'create' => CreateProject::route('/create'),
            'edit' => EditProject::route('/{record}/edit'),
        ];
    }
}
