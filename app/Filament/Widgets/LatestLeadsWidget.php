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
                    LeadStatus::Spam => 'Spam',
                }),
                TextColumn::make('created_at')->label('التاريخ')->since(),
            ])
            ->paginated(false);
    }
}
