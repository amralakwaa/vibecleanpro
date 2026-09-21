<?php

namespace App\Filament\Resources\Projects;

use App\Enums\LocationConfidence;
use App\Enums\LocationEvidenceType;
use App\Enums\LocationStatus;
use App\Filament\Resources\Projects\Pages\ManageLocationEvidences;
use App\Models\Project;
use App\Services\Seo\LocationEvidenceWorkflow;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class LocationEvidenceResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $slug = 'projects/location-evidence-review';

    protected static ?string $modelLabel = 'مراجعة دليل الموقع';

    protected static ?string $pluralModelLabel = 'مراجعة أدلة المواقع';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|\UnitEnum|null $navigationGroup = 'المحتوى';

    protected static ?int $navigationSort = 5;

    public static function getEloquentQuery(): Builder
    {
        // Only show projects that are pending_review
        return parent::getEloquentQuery()
            ->where('location_status', LocationStatus::PendingReview->value);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('المشروع')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (Project $record): string => $record->services()->pluck('name')->join(', ') ?: 'لا يوجد خدمات مرجعية'),

                TextColumn::make('area.name')
                    ->label('الحي المقترح')
                    ->badge()
                    ->color('success'),

                TextColumn::make('location_evidence_type')
                    ->label('نوع الدليل')
                    ->formatStateUsing(fn ($state) => $state ? LocationEvidenceType::tryFrom($state)?->label() : '—'),

                TextColumn::make('location_evidence_reference')
                    ->label('مرجع الدليل')
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }

                        return $state;
                    }),

                TextColumn::make('location_confidence')
                    ->label('الثقة المقترحة')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 3 => 'success',
                        $state == 2 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn ($state) => $state ? LocationConfidence::tryFrom($state)?->label() : '—'),

            ])
            ->actions([
                EditAction::make()
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil')
                    ->form([
                        Select::make('area_id')
                            ->label('الحي المقترح')
                            ->relationship('area', 'name')
                            ->searchable()
                            ->required(),
                        Select::make('location_evidence_type')
                            ->label('نوع الدليل')
                            ->options(LocationEvidenceType::options())
                            ->required(),
                        TextInput::make('location_evidence_reference')
                            ->label('مرجع الدليل')
                            ->required(),
                        Select::make('location_confidence')
                            ->label('الثقة المقترحة')
                            ->options(LocationConfidence::options())
                            ->required(),
                    ]),

                Action::make('approve')
                    ->label('اعتماد')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('اعتماد الدليل الجغرافي')
                    ->modalDescription('باعتماد هذا الدليل، سيُصبح موقع المشروع موثقًا رسميًا وسيبدأ بالتأثير على الـ SEO للصفحة والحي. هل أنت متأكد؟')
                    ->action(function (Project $record) {
                        try {
                            app(LocationEvidenceWorkflow::class)->approve($record, auth()->id());
                            Notification::make()->title('تم اعتماد الدليل الجغرافي بنجاح.')->success()->send();
                        } catch (ValidationException $e) {
                            Notification::make()->title('تعذّر الاعتماد')->body(implode(' ', array_merge(...array_values($e->errors()))))->danger()->send();
                        }
                    }),

                Action::make('reject')
                    ->label('رفض')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('رفض الدليل الجغرافي')
                    ->modalDescription('سيتم رفض هذا الدليل المقترح. تبقى بيانات الدليل محفوظة للمراجعة المستقبلية.')
                    ->action(function (Project $record) {
                        try {
                            app(LocationEvidenceWorkflow::class)->reject($record, auth()->id());
                            Notification::make()->title('تم رفض الدليل الجغرافي.')->warning()->send();
                        } catch (ValidationException $e) {
                            Notification::make()->title('تعذّر الرفض')->body(implode(' ', array_merge(...array_values($e->errors()))))->danger()->send();
                        }
                    }),
            ])
            ->emptyStateHeading('لا توجد أدلة معلقة')
            ->emptyStateDescription('شغل الأمر projects:location-discover لاكتشاف أدلة جديدة.')
            ->emptyStateIcon('heroicon-o-check-badge');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLocationEvidences::route('/'),
        ];
    }
}
