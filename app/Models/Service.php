<?php

namespace App\Models;

use App\Enums\ServicePricingMode;
use App\Models\Concerns\HasPage;
use App\Support\Pricing\PublicPrice;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'service_category_id', 'featured_media_id', 'name', 'short_description', 'icon', 'is_featured', 'sort_order',
    'pricing_mode', 'price_min', 'price_max', 'price_unit', 'price_note', 'show_price',
])]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory, HasPage, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'pricing_mode' => ServicePricingMode::class,
            'price_min' => 'decimal:2',
            'price_max' => 'decimal:2',
            'show_price' => 'boolean',
        ];
    }

    /**
     * The price the public may see, or null when there is none to show.
     */
    public function publicPrice(): ?PublicPrice
    {
        return PublicPrice::forService($this);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function featuredMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_media_id');
    }

    public function areas(): BelongsToMany
    {
        return $this->belongsToMany(Area::class, 'service_area')
            ->withPivot('is_active')
            ->withTimestamps();
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_service')->withTimestamps();
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_service')->withTimestamps();
    }

    public function offers(): BelongsToMany
    {
        return $this->belongsToMany(Offer::class, 'offer_service')->withTimestamps();
    }

    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }
}
