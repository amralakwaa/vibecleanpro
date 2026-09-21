<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\LocationEvidenceResource;
use Filament\Resources\Pages\ManageRecords;

class ManageLocationEvidences extends ManageRecords
{
    protected static string $resource = LocationEvidenceResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
