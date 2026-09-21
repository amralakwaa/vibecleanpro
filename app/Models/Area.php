<?php

namespace App\Models;

use App\Enums\AreaTier;
use App\Models\Concerns\HasPage;
use App\Observers\AreaObserver;
use Database\Factories\AreaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(AreaObserver::class)]
#[Fillable(['area_group_id', 'tier', 'promoted_at', 'promotion_reason', 'promoted_by', 'name', 'slug', 'sort_order'])]
class Area extends Model
{
    /** @use HasFactory<AreaFactory> */
    use HasFactory, HasPage, SoftDeletes;

    protected function casts(): array
    {
        return [
            'tier' => AreaTier::class,
            'promoted_at' => 'datetime',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(AreaGroup::class, 'area_group_id');
    }

    public function promotedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'promoted_by');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_area')
            ->withPivot('is_active')
            ->withTimestamps();
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_area')->withTimestamps();
    }

    public function offers(): BelongsToMany
    {
        return $this->belongsToMany(Offer::class, 'offer_area')->withTimestamps();
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
