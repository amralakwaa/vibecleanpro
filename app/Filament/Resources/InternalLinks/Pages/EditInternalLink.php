<?php

namespace App\Filament\Resources\InternalLinks\Pages;

use App\Filament\Resources\InternalLinks\InternalLinkResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInternalLink extends EditRecord
{
    protected static string $resource = InternalLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
