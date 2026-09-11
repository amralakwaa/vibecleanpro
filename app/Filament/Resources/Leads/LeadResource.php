<?php

namespace App\Filament\Resources\Leads;

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
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use UnitEnum;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static string|UnitEnum|null $navigationGroup = 'المبيعات';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'عميل محتمل';

    protected static ?string $pluralModelLabel = 'العملاء المحتملون (Leads)';

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
                        ->label('المنطقة')
                        ->relationship('area', 'name')
                        ->searchable()
                        ->preload(),
                    Select::make('status')
                        ->label('الحالة')
                        ->options([
                            LeadStatus::New->value => 'جديد',
                            LeadStatus::Contacted->value => 'تم التواصل',
                            LeadStatus::Qualified->value => 'مؤهل',
                            LeadStatus::Quoted->value => 'تم إرسال عرض سعر',
                            LeadStatus::Won->value => 'مكتمل (فوز)',
                            LeadStatus::Lost->value => 'خسارة',
                            LeadStatus::Spam->value => 'غير مرغوب (Spam)',
                        ])
                        ->required(),
                    Textarea::make('notes')->label('ملاحظات فريق المبيعات')->rows(3)->columnSpanFull(),
                ])
                ->columns(3),

            Section::make('مصدر الطلب')
                ->schema([
                    Placeholder::make('source')
                        ->label('نموذج الإرسال')
                        ->content(fn (?Lead $record) => match ($record?->source) {
                            'quote_form' => 'طلب عرض سعر',
                            'contact_form' => 'نموذج التواصل',
                            default => $record?->source ?? '—',
                        }),
                    Placeholder::make('landing_page')
                        ->label('صفحة الدخول الأولى')
                        ->content(fn (?Lead $record) => $record?->landing_page ?? '—'),
                    Placeholder::make('source_page_info')
                        ->label('الصفحة المصدر')
                        ->content(fn (?Lead $record) => $record?->sourcePage?->title ?? '—'),
                    Placeholder::make('ip_address')->label('عنوان IP')->content(fn (?Lead $record) => $record?->ip_address ?? '—'),
                ])
                ->columns(2)
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
                TextColumn::make('area.name')->label('المنطقة')->badge(),
                TextColumn::make('status')->label('الحالة')->badge()->formatStateUsing(fn (LeadStatus $state) => match ($state) {
                    LeadStatus::New => 'جديد',
                    LeadStatus::Contacted => 'تم التواصل',
                    LeadStatus::Qualified => 'مؤهل',
                    LeadStatus::Quoted => 'عرض سعر',
                    LeadStatus::Won => 'فوز',
                    LeadStatus::Lost => 'خسارة',
                    LeadStatus::Spam => 'Spam',
                })->color(fn (LeadStatus $state) => match ($state) {
                    LeadStatus::New => 'info',
                    LeadStatus::Contacted, LeadStatus::Qualified, LeadStatus::Quoted => 'warning',
                    LeadStatus::Won => 'success',
                    LeadStatus::Lost, LeadStatus::Spam => 'danger',
                }),
                TextColumn::make('created_at')->label('تاريخ الاستلام')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        LeadStatus::New->value => 'جديد',
                        LeadStatus::Contacted->value => 'تم التواصل',
                        LeadStatus::Qualified->value => 'مؤهل',
                        LeadStatus::Quoted->value => 'عرض سعر',
                        LeadStatus::Won->value => 'فوز',
                        LeadStatus::Lost->value => 'خسارة',
                        LeadStatus::Spam->value => 'Spam',
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
            'index' => ListLeads::route('/'),
            'create' => CreateLead::route('/create'),
            'edit' => EditLead::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', LeadStatus::New)->count();
    }
}
