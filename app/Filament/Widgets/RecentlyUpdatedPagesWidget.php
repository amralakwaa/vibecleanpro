<?php

namespace App\Filament\Widgets;

use App\Enums\PageStatus;
use App\Models\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RecentlyUpdatedPagesWidget extends TableWidget
{
    protected static ?string $heading = 'أحدث المحتوى المعدَّل';

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return (bool) Auth::user()?->can('view_any_page');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Page::query()->latest('updated_at')->limit(5))
            ->columns([
                TextColumn::make('title')->label('العنوان'),
                TextColumn::make('status')->label('الحالة')->badge()
                    ->formatStateUsing(fn (PageStatus $state) => match ($state) {
                        PageStatus::Published => 'منشورة',
                        PageStatus::Draft => 'مسودة',
                        PageStatus::Archived => 'مؤرشفة',
                    })
                    ->color(fn (PageStatus $state) => match ($state) {
                        PageStatus::Published => 'success',
                        PageStatus::Draft => 'gray',
                        PageStatus::Archived => 'warning',
                    }),
                TextColumn::make('updated_at')->label('آخر تعديل')->since(),
            ])
            ->paginated(false);
    }
}
