<?php

namespace App\Filament\Resources\Leads;

use App\Enums\LeadLostReason;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Filament\Resources\Leads\Pages\CreateLead;
use App\Filament\Resources\Leads\Pages\EditLead;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Models\Lead;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static string|UnitEnum|null $navigationGroup = 'المبيعات';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'عميل محتمل';

    protected static ?string $pluralModelLabel = 'العملاء المحتملون (Leads)';

    /**
     * A salesperson works their own leads and the unassigned queue; only
     * roles that may delete leads (management) see everyone's pipeline.
     */
    public static function isRestrictedToOwnLeads(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->hasRole('Sales') && ! $user->can('delete_lead');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (static::isRestrictedToOwnLeads()) {
            $query->where(fn (Builder $leads) => $leads->whereNull('assigned_to')->orWhere('assigned_to', auth()->id()));
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('بيانات التواصل')
                ->schema([
                    TextInput::make('name')->label('الاسم')->required()->maxLength(255),
                    TextInput::make('phone')->label('الجوال')->required()->tel()->maxLength(255),
                    TextInput::make('email')->label('البريد الإلكتروني')->email()->maxLength(255),
                    Textarea::make('message')->label('الرسالة')->rows(3)->columnSpanFull()->disabled(),
                ])
                ->columns(2),

            Section::make('التصنيف والمتابعة')
                ->schema([
                    Select::make('service_id')
                        ->label('الخدمة المطلوبة')
                        ->relationship('service', 'name')
                        ->searchable()
                        ->preload(),
                    Select::make('area_id')
                        ->label('الحي')
                        ->relationship('area', 'name')
                        ->searchable()
                        ->preload(),
                    Select::make('status')
                        ->label('الحالة')
                        ->options(LeadStatus::options())
                        ->default(LeadStatus::New->value)
                        ->live()
                        ->required(),
                    Select::make('lost_reason')
                        ->label('سبب الخسارة')
                        ->options(LeadLostReason::options())
                        ->visible(fn (Get $get) => $get('status') === LeadStatus::Lost->value || $get('status') === LeadStatus::Lost)
                        ->required(fn (Get $get) => $get('status') === LeadStatus::Lost->value || $get('status') === LeadStatus::Lost),
                    Select::make('assigned_to')
                        ->label('المسؤول')
                        ->relationship('assignee', 'name')
                        ->searchable()
                        ->preload(),
                    Select::make('project_id')
                        ->label('المشروع الناتج')
                        ->relationship('project', 'title')
                        ->searchable()
                        ->helperText('بعد التنفيذ وموافقة العميل على النشر فقط.')
                        ->visible(fn (Get $get) => $get('status') === LeadStatus::Completed->value || $get('status') === LeadStatus::Completed),
                    Toggle::make('consent_marketing')->label('وافق على التواصل لاحقًا (عروض/تقييم)'),
                    Textarea::make('notes')->label('ملاحظات فريق المبيعات')->rows(3)->columnSpanFull(),
                ])
                ->columns(3),

            Section::make('مصدر الطلب')
                ->description('طلبات واتساب والاتصال تُسجل يدويًا هنا. الصق "رمز الصفحة" الموجود في رسالة واتساب ليُربط الطلب بالصفحة والخدمة والحي تلقائيًا.')
                ->schema([
                    Select::make('source')
                        ->label('القناة')
                        ->options(LeadSource::manualOptions())
                        ->default(LeadSource::Whatsapp->value)
                        ->required()
                        ->visibleOn('create'),
                    Placeholder::make('source_label')
                        ->label('القناة')
                        ->content(fn (?Lead $record) => LeadSource::labelFor($record?->source))
                        ->hiddenOn('create'),
                    TextInput::make('attribution_code')
                        ->label('رمز الصفحة (من رسالة واتساب)')
                        ->placeholder('V-XXXXXXX')
                        ->maxLength(12)
                        ->regex('/^(V-)?[0-9A-Za-z]{7}$/'),
                    Placeholder::make('source_page_info')
                        ->label('الصفحة المصدر')
                        ->content(fn (?Lead $record) => $record?->sourcePage?->title ?? '—'),
                    Placeholder::make('landing_page')
                        ->label('صفحة الدخول الأولى')
                        ->content(fn (?Lead $record) => $record?->landing_page ?? '—'),
                    Placeholder::make('device_type')
                        ->label('الجهاز')
                        ->content(fn (?Lead $record) => $record?->device_type ?? '—'),
                    Placeholder::make('ip_address')->label('عنوان IP')->content(fn (?Lead $record) => $record?->ip_address ?? '—'),
                ])
                ->columns(3)
                ->collapsible(),

            Section::make('مراحل الطلب')
                ->schema([
                    Placeholder::make('created_at')->label('الاستلام')->content(fn (?Lead $record) => $record?->created_at?->format('Y-m-d H:i') ?? '—'),
                    Placeholder::make('contacted_at')->label('أول تواصل')->content(fn (?Lead $record) => $record?->contacted_at?->format('Y-m-d H:i') ?? '—'),
                    Placeholder::make('quoted_at')->label('عرض السعر')->content(fn (?Lead $record) => $record?->quoted_at?->format('Y-m-d H:i') ?? '—'),
                    Placeholder::make('completed_at')->label('التنفيذ')->content(fn (?Lead $record) => $record?->completed_at?->format('Y-m-d H:i') ?? '—'),
                ])
                ->columns(4)
                ->hiddenOn('create')
                ->collapsed()
                ->collapsible(),

            Section::make('بيانات UTM')
                ->schema([
                    TextInput::make('utm_source')->label('utm_source')->disabled(),
                    TextInput::make('utm_medium')->label('utm_medium')->disabled(),
                    TextInput::make('utm_campaign')->label('utm_campaign')->disabled(),
                    TextInput::make('utm_term')->label('utm_term')->disabled(),
                    TextInput::make('utm_content')->label('utm_content')->disabled(),
                ])
                ->columns(3)
                ->hiddenOn('create')
                ->collapsed()
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('الاسم')->searchable()->weight('medium'),
                TextColumn::make('phone')->label('الجوال')->searchable()->copyable(),
                TextColumn::make('service.name')->label('الخدمة')->badge(),
                TextColumn::make('area.name')->label('الحي')->badge()->toggleable(),
                TextColumn::make('source')->label('القناة')->formatStateUsing(fn (?string $state) => LeadSource::labelFor($state))->toggleable(),
                TextColumn::make('status')->label('الحالة')->badge()
                    ->formatStateUsing(fn (LeadStatus $state) => $state->label())
                    ->color(fn (LeadStatus $state) => $state->color()),
                TextColumn::make('assignee.name')->label('المسؤول')->toggleable(),
                TextColumn::make('created_at')->label('تاريخ الاستلام')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label('الحالة')->options(LeadStatus::options()),
                SelectFilter::make('source')->label('القناة')->options(collect(LeadSource::cases())->mapWithKeys(fn (LeadSource $source) => [$source->value => $source->label()])->all()),
                SelectFilter::make('service_id')->label('الخدمة')->relationship('service', 'name'),
                SelectFilter::make('area_id')->label('الحي')->relationship('area', 'name')->searchable(),
                SelectFilter::make('assigned_to')->label('المسؤول')->relationship('assignee', 'name'),
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
            'index' => ListLeads::route('/'),
            'create' => CreateLead::route('/create'),
            'edit' => EditLead::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getEloquentQuery()->where('status', LeadStatus::New)->count();
    }
}
