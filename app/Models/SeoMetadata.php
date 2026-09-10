<?php

namespace App\Models;

use Database\Factories\SeoMetadataFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'meta_title', 'meta_description', 'canonical_url',
    'robots_index', 'robots_follow', 'og_title', 'og_description', 'structured_data',
])]
class SeoMetadata extends Model
{
    /** @use HasFactory<SeoMetadataFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'robots_index' => 'boolean',
            'robots_follow' => 'boolean',
            'structured_data' => 'array',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function ogImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'og_image_media_id');
    }
}
