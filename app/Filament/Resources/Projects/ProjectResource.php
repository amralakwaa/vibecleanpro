<?php

namespace App\Filament\Resources\Projects;

use App\Enums\LocationConfidence;
use App\Enums\LocationEvidenceType;
use App\Enums\LocationSource;
use App\Enums\LocationStatus;
use App\Enums\MediaStage;
use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Enums\ProjectCluster;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Support\MediaPicker;
use App\Models\Article;
use App\Models\Project;
use App\Seo\ProjectSeoPriority;
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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
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
                                    Textarea::make('summary')->label('ملخص المشروع')->rows(3)->columnSpanFull(),
                                    TextInput::make('focus_keyword')
                                        ->label('الكلمة المفتاحية المستهدفة')
                                        ->maxLength(160)
                                        ->helperText('العبارة التي كُتبت هذه الدراسة لتملكها. عنوان ووصف SEO في تبويب «الصفحة والنشر».'),
                                    Select::make('cluster')
                                        ->label('تصنيف المشروع')
                                        ->options(ProjectCluster::options())
                                        ->native(false)
                                        ->live()
                                        ->helperText(fn (?string $state) => $state && $cluster = ProjectCluster::tryFrom($state)
                                            ? $cluster->description()
                                            : 'يُشتق من الخدمة الأساسية، ويُستخدم لربط المشاريع المتشابهة ببعضها.'),
                                    DatePicker::make('completed_at')->label('تاريخ الإنجاز'),
                                    Toggle::make('is_featured')->label('مشروع مميز'),
                                    TextInput::make('sort_order')->label('ترتيب العرض')->numeric()->default(0),
                                ])
                                ->columns(2),
                            Section::make('دراسة الحالة')
                                ->description('ما حدث فعلًا في هذا المشروع. كل حقل اختياري، وما يبقى فارغًا لا يظهر في الصفحة — لا تُكتب هنا تفاصيل غير مؤكدة، ولا اسم عميل دون إذنه.')
                                ->schema([
                                    Textarea::make('challenge')
                                        ->label('المشكلة التي جاء العميل من أجلها')
                                        ->rows(3)
                                        ->maxLength(600)
                                        ->columnSpanFull(),
                                    Textarea::make('site_condition')
                                        ->label('حالة الموقع قبل التنفيذ')
                                        ->rows(3)
                                        ->maxLength(600)
                                        ->columnSpanFull(),
                                    Repeater::make('execution_steps')
                                        ->label('خطوات التنفيذ')
                                        ->schema([
                                            TextInput::make('title')->label('الخطوة')->required()->maxLength(120),
                                            Textarea::make('description')->label('ماذا نُفّذ فيها')->rows(2)->maxLength(400),
                                        ])
                                        ->reorderable()
                                        ->collapsible()
                                        ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                                        ->addActionLabel('إضافة خطوة')
                                        ->defaultItems(0)
                                        ->columnSpanFull(),
                                    Textarea::make('outcome')
                                        ->label('النتيجة')
                                        ->rows(3)
                                        ->maxLength(600)
                                        ->helperText('ما الذي تغيّر فعلًا بعد التنفيذ. بلا أرقام أو نسب غير مقاسة.')
                                        ->columnSpanFull(),
                                    Textarea::make('client_problem')
                                        ->label('المشكلة التي عالجناها')
                                        ->rows(2)
                                        ->maxLength(400)
                                        ->helperText('كما يصفها العميل لنفسه، لا كما نصفها نحن.')
                                        ->columnSpanFull(),
                                    Textarea::make('client_benefit')
                                        ->label('القيمة للعميل')
                                        ->rows(2)
                                        ->maxLength(400)
                                        ->helperText('ما الذي يكسبه — بلا أرقام أو نسب غير مقاسة.')
                                        ->columnSpanFull(),
                                    Textarea::make('execution_difference')
                                        ->label('سبب اختلاف التنفيذ')
                                        ->rows(2)
                                        ->maxLength(400)
                                        ->helperText('لماذا يختلف هذا التنفيذ عن تنفيذ أرخص.')
                                        ->columnSpanFull(),
                                ]),

                            Section::make('أولوية SEO')
                                ->description('تقييم استشاري يُحسب لحظيًا من بيانات المشروع. لا يؤثر على النشر ولا الفهرسة.')
                                ->collapsed()
                                ->schema([
                                    Placeholder::make('seo_priority')
                                        ->hiddenLabel()
                                        ->content(function (?Project $record): HtmlString {
                                            if (! $record) {
                                                return new HtmlString('<p class="text-sm text-gray-500">يظهر التقييم بعد حفظ المشروع.</p>');
                                            }

                                            $explanation = app(ProjectSeoPriority::class)->explain($record);
                                            $list = fn (array $items, string $colour) => $items === []
                                                ? ''
                                                : '<ul class="mt-1 list-disc ms-5 text-sm '.$colour.'"><li>'.implode('</li><li>', array_map('e', $items)).'</li></ul>';

                                            return new HtmlString(
                                                '<p class="text-sm"><strong>Tier '.e($explanation['tier']).'</strong> — '.e($explanation['score']).'/25</p>'
                                                .'<p class="mt-2 text-sm text-gray-600">'.e($explanation['verdict']).'</p>'
                                                .($explanation['strengths'] !== [] ? '<p class="mt-3 text-sm font-medium">عوامل القوة</p>'.$list($explanation['strengths'], 'text-gray-600') : '')
                                                .($explanation['gaps'] !== [] ? '<p class="mt-3 text-sm font-medium">ما ينقصه</p>'.$list($explanation['gaps'], 'text-gray-600') : '')
                                            );
                                        })
                                        ->columnSpanFull(),
                                    Placeholder::make('proof_signals')
                                        ->label('إشارات قوة الدليل')
                                        ->content(function (?Project $record): HtmlString {
                                            if (! $record) {
                                                return new HtmlString('<p class="text-sm text-gray-500">تظهر بعد حفظ المشروع.</p>');
                                            }

                                            $page = $record->page;
                                            $photos = $record->media()->count();
                                            $stages = $record->media()->pluck('project_media.stage')->filter()->unique();
                                            $articles = $page
                                                ? Article::whereHas('services', fn ($q) => $q->whereIn('services.id', $record->services()->pluck('services.id')))->count()
                                                : 0;

                                            $signals = [
                                                'عدد الصور' => $photos > 0 ? $photos.' صورة' : null,
                                                'قبل / بعد' => $stages->contains('before') && $stages->contains('after') ? 'زوج موثَّق' : null,
                                                'خطوات تنفيذ' => is_array($record->execution_steps) && $record->execution_steps !== [] ? count($record->execution_steps).' خطوات' : null,
                                                'نتيجة مكتوبة' => filled($record->outcome) ? 'نعم' : null,
                                                'خدمة مرتبطة' => $record->services()->count() > 0 ? $record->services()->count().' خدمة' : null,
                                                'مقالات داعمة' => $articles > 0 ? $articles.' مقال' : null,
                                            ];

                                            $rows = collect($signals)->map(fn (?string $value, string $label) => $value
                                                ? '<li><span class="text-green-600">✔</span> '.e($label).': '.e($value).'</li>'
                                                : '<li class="text-gray-400"><span>✕</span> '.e($label).': ينقص</li>')->implode('');

                                            return new HtmlString('<ul class="text-sm space-y-1">'.$rows.'</ul>');
                                        })
                                        ->columnSpanFull(),
                                ]),

                            Section::make('دليل الموقع (Location Evidence)')
                                ->description('لا يُستخدم الموقع في السيو إلا إذا فُعِّل «موثَّق». الحي المخترع إشارة محلية زائفة.')
                                ->collapsed()
                                ->schema([
                                    Select::make('area_id')
                                        ->label('المنطقة (الحي)')
                                        ->relationship('area', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->helperText('حي الرياض الرسمي المرتبط بالمشروع — يربطه بصفحة الحي عند ترقيتها.'),
                                    TextInput::make('city')->label('المدينة')->maxLength(120)->default('الرياض'),
                                    TextInput::make('neighborhood')->label('المجاورة / الجزء من الحي')->maxLength(120),
                                    TextInput::make('landmark')->label('معلم قريب')->maxLength(160),
                                    Textarea::make('location_note')
                                        ->label('وصف موقع المشروع')
                                        ->rows(2)
                                        ->maxLength(400)
                                        ->helperText('وصف داخلي للموقع. لا يُنشر إن كان يكشف هوية العميل.')
                                        ->columnSpanFull(),
                                    Select::make('location_source')
                                        ->label('مصدر المعلومة')
                                        ->options(LocationSource::options())
                                        ->native(false)
                                        ->helperText('القناة التي وصل بها الموقع.'),
                                    Select::make('location_evidence_type')
                                        ->label('نوع الدليل')
                                        ->options(LocationEvidenceType::options())
                                        ->native(false)
                                        ->live()
                                        ->helperText(fn (?string $state) => $state && $t = LocationEvidenceType::tryFrom($state)
                                            ? $t->description()
                                            : 'نوع المستند الذي يثبت الموقع — مطلوب للاعتماد.'),
                                    Select::make('location_confidence')
                                        ->label('مستوى الثقة')
                                        ->options(LocationConfidence::options())
                                        ->default(0)
                                        ->native(false)
                                        ->helperText('لا يُستخدم الموقع في السيو إلا عند المستوى 3 (دليل مصوَّر) فأعلى.'),
                                    TextInput::make('location_evidence_reference')
                                        ->label('مرجع الإثبات')
                                        ->maxLength(255)
                                        ->helperText('اسم ملف الصورة، أو مستند، أو ملاحظة تحقق.'),
                                    Select::make('location_status')
                                        ->label('حالة التحقق')
                                        ->options(LocationStatus::options())
                                        ->default('draft')
                                        ->native(false)
                                        ->helperText('الاعتماد يتطلب: ثقة ≥ 3 + نوع دليل + مرجع. كل تغيير حالة يُسجَّل في التاريخ.'),
                                    Placeholder::make('verification_stamp')
                                        ->label('توقيع الاعتماد')
                                        ->content(fn (?Project $record) => $record?->verified_at
                                            ? ($record->verifiedBy?->name ?? 'غير معروف').' — '.$record->verified_at->format('Y-m-d H:i')
                                            : 'لم يُعتمد بعد.')
                                        ->columnSpanFull(),
                                ])
                                ->columns(2),

                            Section::make('تأكيد المالك')
                                ->description('لا تُنشر صفحة مشروع قبل أن يؤكد المالك أن العمل نُفّذ كما هو موصوف: الخدمة، الحي، التاريخ، والصور.')
                                ->schema([
                                    DateTimePicker::make('owner_confirmed_at')
                                        ->label('تاريخ تأكيد المالك')
                                        ->helperText('فارغ = مرشّح من مكتبة الصور لم يُؤكَّد بعد.')
                                        ->disabled(fn (): bool => ! (auth()->user()?->can('confirm_project') ?? false)),
                                    TextInput::make('source_ref')->label('مرجع المكتبة')->disabled()->dehydrated(false),
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
                                    MediaPicker::makeForProject('media_id', 'الصورة')->required()->columnSpan(2),
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
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('العنوان')->searchable()->weight('medium'),
                TextColumn::make('area.name')->label('المنطقة')->badge(),
                TextColumn::make('seo_priority_tier')
                    ->label('أولوية SEO')
                    ->badge()
                    ->sortable()
                    ->color(fn (?string $state) => match ($state) {
                        'A' => 'success',
                        'B' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state, Project $record) => $state ? $state.' · '.$record->seo_priority_score : '—')
                    ->tooltip(fn (Project $record) => $record->seo_priority_updated_at
                        ? 'آخر حساب: '.$record->seo_priority_updated_at->diffForHumans()
                        : 'لم يُحسب بعد — شغّل projects:seo-priority --store'),
                TextColumn::make('cluster')
                    ->label('التصنيف')
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn (?string $state) => $state ? (ProjectCluster::tryFrom($state)?->label() ?? $state) : '—')
                    ->tooltip(fn (?string $state) => $state ? ProjectCluster::tryFrom($state)?->description() : null),
                TextColumn::make('media_count')->label('عدد الصور')->counts('media'),
                TextColumn::make('completed_at')->label('تاريخ الإنجاز')->date('Y-m-d')->sortable(),
                IconColumn::make('is_featured')->label('مميز')->boolean(),
                IconColumn::make('owner_confirmed_at')->label('مؤكَّد')->boolean()->getStateUsing(fn (Project $record) => $record->owner_confirmed_at !== null),
                IconColumn::make('page.id')->label('له صفحة')->boolean()->getStateUsing(fn (Project $record) => $record->page !== null),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('area_id')->label('المنطقة')->relationship('area', 'name'),
                SelectFilter::make('seo_priority_tier')->label('أولوية SEO')->options(['A' => 'Tier A', 'B' => 'Tier B', 'C' => 'Tier C']),
                SelectFilter::make('cluster')->label('التصنيف')->options(ProjectCluster::options()),
                TernaryFilter::make('owner_confirmed_at')->label('تأكيد المالك')->nullable(),
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
