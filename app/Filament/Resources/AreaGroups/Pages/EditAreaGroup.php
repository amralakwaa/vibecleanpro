<?php

namespace App\Filament\Resources\AreaGroups\Pages;

use App\Filament\Resources\AreaGroups\AreaGroupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAreaGroup extends EditRecord
{
    protected static string $resource = AreaGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
