<?php

namespace App\Models;

use App\Enums\OfferAvailability;
use App\Models\Concerns\HasPage;
use Database\Factories\OfferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['featured_media_id', 'title', 'discount_label', 'offer_price', 'starts_at', 'ends_at', 'is_active', 'sort_order'])]
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
            'offer_price' => 'decimal:2',
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

    /**
     * Derived from is_active/starts_at/ends_at - never a separate status
     * to keep in sync by hand. An editor-disabled offer (is_active=false)
     * is always Expired regardless of its dates: that flag is the
     * explicit "pull this down" switch.
     */
    public function availability(): OfferAvailability
    {
        if (! $this->is_active) {
            return OfferAvailability::Expired;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return OfferAvailability::Expired;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return OfferAvailability::Scheduled;
        }

        return OfferAvailability::Active;
    }
}
