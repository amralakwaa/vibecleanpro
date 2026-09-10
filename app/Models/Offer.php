<?php

namespace App\Models;

use App\Models\Concerns\HasPage;
use Database\Factories\OfferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['featured_media_id', 'title', 'discount_label', 'starts_at', 'ends_at', 'is_active', 'sort_order'])]
class Offer extends Model
{
    /** @use HasFactory<OfferFactory> */
    use HasFactory, HasPage, SoftDeletes;

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function featuredMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_media_id');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'offer_service')->withTimestamps();
    }

    public function areas(): BelongsToMany
    {
        return $this->belongsToMany(Area::class, 'offer_area')->withTimestamps();
    }
}
