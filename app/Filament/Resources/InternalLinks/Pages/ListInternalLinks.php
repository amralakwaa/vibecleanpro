<?php

namespace App\Filament\Resources\InternalLinks\Pages;

use App\Filament\Resources\InternalLinks\InternalLinkResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInternalLinks extends ListRecords
{
    protected static string $resource = InternalLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
