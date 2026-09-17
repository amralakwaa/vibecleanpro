<?php

namespace App\Filament\Support;

use App\Models\Media as MediaModel;
use Filament\Forms\Components\Select;

/**
 * A searchable "pick an existing Media Library item" field, reused across
 * every content block that needs an image, so editors reuse uploads
 * instead of uploading the same picture again in every block.
 */
class MediaPicker
{
    public static function make(string $name = 'media_id', string $label = 'الصورة'): Select
    {
        return static::baseSelect($name, $label);
    }

    public static function makeMultiple(string $name = 'media_ids', string $label = 'الصور'): Select
    {
        return static::baseSelect($name, $label)->multiple();
    }

    /**
     * For project photographs: the licensed stock illustrations in the
     * library are never offered, so a stock picture cannot be attached
     * to a project as if it were the company's own work.
     */
    public static function makeForProject(string $name = 'media_id', string $label = 'الصورة'): Select
    {
        return static::baseSelect($name, $label, excludeLibraryStock: true);
    }

    private static function baseSelect(string $name, string $label, bool $excludeLibraryStock = false): Select
    {
        $query = fn () => MediaModel::query()->when($excludeLibraryStock, fn ($query) => $query->excludingLibraryStock());

        return Select::make($name)
            ->label($label)
            ->searchable()
            ->preload()
            ->getSearchResultsUsing(
                fn (string $search) => $query()
                    ->where(fn ($q) => $q->where('original_filename', 'like', "%{$search}%")->orWhere('alt_text', 'like', "%{$search}%"))
                    ->limit(20)
                    ->pluck('original_filename', 'id')
            )
            ->getOptionLabelUsing(fn ($value) => MediaModel::find($value)?->original_filename)
            ->getOptionLabelsUsing(
                fn (array $values) => MediaModel::query()->whereKey($values)->pluck('original_filename', 'id')
            );
    }
}
