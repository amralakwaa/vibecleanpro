<?php

namespace App\Models;

use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['disk', 'path', 'original_filename', 'mime_type', 'size', 'width', 'height', 'alt_text', 'caption', 'variants'])]
class Media extends Model
{
    public const LIBRARY_DIRECTORY = 'media/library';

    /** @use HasFactory<MediaFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'variants' => 'array',
        ];
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * Stock illustrations imported by InitialMediaSeeder live under one
     * directory. They are fine for the hero, services and generic visuals,
     * but must never be presented as the company's own project work - so
     * the project media picker filters them out.
     */
    public function isLibraryStock(): bool
    {
        return str_starts_with($this->path, self::LIBRARY_DIRECTORY.'/');
    }

    /**
     * @param  Builder<Media>  $query
     */
    public function scopeExcludingLibraryStock(Builder $query): void
    {
        $query->where('path', 'not like', self::LIBRARY_DIRECTORY.'/%');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_media')
            ->withPivot(['stage', 'sort_order'])
            ->withTimestamps();
    }
}
