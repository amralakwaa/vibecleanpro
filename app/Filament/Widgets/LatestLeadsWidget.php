<?php

namespace App\Filament\Widgets;

use App\Enums\LeadStatus;
use App\Models\Lead;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class LatestLeadsWidget extends TableWidget
{
    protected static ?int $sort = 4;

    protected static ?string $heading = 'أحدث العملاء المحتملين';

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return (bool) Auth::user()?->can('view_any_lead');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Lead::query()->latest()->limit(5))
            ->columns([
                TextColumn::make('name')->label('الاسم'),
                TextColumn::make('phone')->label('الجوال'),
                TextColumn::make('status')->label('الحالة')->badge()->formatStateUsing(fn (LeadStatus $state) => match ($state) {
                    LeadStatus::New => 'جديد',
                    LeadStatus::Contacted => 'تم التواصل',
                    LeadStatus::Qualified => 'مؤهل',
                    LeadStatus::Quoted => 'عرض سعر',
                    LeadStatus::Won => 'فوز',
                    LeadStatus::Lost => 'خسارة',
                    LeadStatus::Spam => 'مزعج',
                }),
                TextColumn::make('created_at')->label('التاريخ')->since(),
            ])
            // Without this the widget falls back to Filament's generic
            // "لا يوجد leads" - the raw table name, in English, inside an
            // otherwise Arabic panel. On a brand new site an empty table
            // here is also the normal state, not a fault, so it says so.
            ->emptyStateHeading('لا توجد طلبات بعد')
            ->emptyStateDescription('كل طلب يصل من نموذج الموقع أو من زر واتساب سيظهر هنا فور وصوله.')
            ->paginated(false);
    }
}
