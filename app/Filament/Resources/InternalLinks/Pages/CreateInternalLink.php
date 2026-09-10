<?php

namespace App\Filament\Resources\InternalLinks\Pages;

use App\Filament\Resources\InternalLinks\InternalLinkResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInternalLink extends CreateRecord
{
    protected static string $resource = InternalLinkResource::class;
}
