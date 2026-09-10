<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;

    /**
     * @var array<string, array{type: string, data: array}>
     */
    protected array $pendingContentBlocks = [];

    /**
     * content_blocks lives in its own table (one row per block, ordered by
     * position), but the Builder field works with one JSON array - so it is
     * pulled out here and translated into rows in afterCreate() instead of
     * being saved as a column on pages.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingContentBlocks = $data['content_blocks_builder'] ?? [];
        unset($data['content_blocks_builder']);

        return $data;
    }

    protected function afterCreate(): void
    {
        foreach (array_values($this->pendingContentBlocks) as $position => $item) {
            $this->record->contentBlocks()->create([
                'type' => $item['type'],
                'data' => $item['data'] ?? [],
                'position' => $position,
            ]);
        }
    }
}
