<?php

namespace App\Filament\Resources\Credentials\Tables;

use App\Enums\CredentialStatus;
use App\Enums\CredentialType;
use App\Models\Credential;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CredentialsTable
{
    public static function configure(Table $table): Table
    {
        $typeOptions = collect(CredentialType::cases())->mapWithKeys(fn (CredentialType $t) => [$t->value => $t->label()])->all();
        $statusOptions = collect(CredentialStatus::cases())->mapWithKeys(fn (CredentialStatus $s) => [$s->value => $s->label()])->all();

        return $table
            ->columns([
                TextColumn::make('name_ar')->label('الاسم')->searchable()->wrap(),
                TextColumn::make('document_code')->label('الرمز')->searchable()->badge()->color('gray')->placeholder('—'),
                TextColumn::make('credential_type')->label('النوع')->badge()
                    ->formatStateUsing(fn (CredentialType $state) => $state->label()),
                TextColumn::make('status')->label('الحالة')->badge()
                    ->formatStateUsing(fn (CredentialStatus $state) => $state->label())
                    ->color(fn (CredentialStatus $state) => $state->color()),
                IconColumn::make('is_internal')->label('داخلي')->boolean(),
                IconColumn::make('is_public')->label('ظاهر')->boolean(),
                TextColumn::make('expires_at')->label('ينتهي')->date()->placeholder('—')
                    ->color(fn (?Credential $record) => $record?->isExpiringSoon() ? 'warning' : null)
                    ->description(fn (?Credential $record) => $record?->isExpiringSoon() ? 'قريب الانتهاء' : null),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('credential_type')->label('النوع')->options($typeOptions),
                SelectFilter::make('status')->label('الحالة')->options($statusOptions),
                TernaryFilter::make('is_internal')->label('داخلي/خارجي')
                    ->trueLabel('داخلي')->falseLabel('خارجي'),
                TernaryFilter::make('is_public')->label('العرض')
                    ->trueLabel('ظاهر')->falseLabel('مخفي'),
                Filter::make('expiring_soon')->label('قريب الانتهاء')
                    ->query(fn ($query) => $query->whereNotNull('expires_at')
                        ->whereBetween('expires_at', [now(), now()->addDays(45)])),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
