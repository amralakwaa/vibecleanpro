<?php

namespace App\Models;

use App\Models\Concerns\HasPage;
use Database\Factories\AreaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['area_group_id', 'name', 'slug', 'sort_order'])]
class Area extends Model
{
    /** @use HasFactory<AreaFactory> */
    use HasFactory, HasPage, SoftDeletes;

    public function group(): BelongsTo
    {
        return $this->belongsTo(AreaGroup::class, 'area_group_id');
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
