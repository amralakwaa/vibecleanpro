<?php

namespace App\Filament\Resources\AreaGroups\Pages;

use App\Filament\Resources\AreaGroups\AreaGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAreaGroups extends ListRecords
{
    protected static string $resource = AreaGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
