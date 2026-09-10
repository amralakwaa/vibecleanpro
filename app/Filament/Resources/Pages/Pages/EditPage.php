<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    /**
     * @var array<string, array{type: string, data: array}>
     */
    protected array $pendingContentBlocks = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * Reverse of the create-side translation: load the page's content_blocks
     * rows (ordered) into the shape the Builder field expects.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['content_blocks_builder'] = $this->record->contentBlocks()
            ->orderBy('position')
            ->get()
            ->mapWithKeys(fn ($block) => [
                (string) Str::uuid() => ['type' => $block->type, 'data' => $block->data ?? []],
            ])
            ->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingContentBlocks = $data['content_blocks_builder'] ?? [];
        unset($data['content_blocks_builder']);

        return $data;
    }

    /**
     * Rewrites content_blocks only when the submitted blocks actually
     * differ from what's stored - both to avoid pointless writes on every
     * save, and so pages.updated_at (touched by ContentBlock, see its
     * #[Touches] attribute) only moves for a real content change, keeping
     * the sitemap's lastmod meaningful.
     */
    protected function afterSave(): void
    {
        $incoming = collect(array_values($this->pendingContentBlocks))
            ->map(fn (array $item) => ['type' => $item['type'], 'data' => $item['data'] ?? []])
            ->all();

        $current = $this->record->contentBlocks()
            ->orderBy('position')
            ->get(['type', 'data'])
            ->map(fn ($block) => ['type' => $block->type, 'data' => $block->data ?? []])
            ->all();

        if ($incoming === $current) {
            return;
        }

        $this->record->contentBlocks()->delete();

        foreach ($incoming as $position => $item) {
            $this->record->contentBlocks()->create([
                'type' => $item['type'],
                'data' => $item['data'],
                'position' => $position,
            ]);
        }
    }
}
