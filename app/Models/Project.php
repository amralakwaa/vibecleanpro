<?php

namespace App\Models;

use App\Enums\MediaStage;
use App\Models\Concerns\HasPage;
use App\Observers\ProjectObserver;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['source_ref', 'area_id', 'title', 'summary', 'completed_at', 'owner_confirmed_at', 'is_featured', 'sort_order'])]
#[ObservedBy(ProjectObserver::class)]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, HasPage, SoftDeletes;

    protected function casts(): array
    {
        return [
            'completed_at' => 'date',
            'owner_confirmed_at' => 'datetime',
            'is_featured' => 'boolean',
        ];
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'project_service')->withTimestamps();
    }

    public function media(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'project_media')
            ->withPivot(['stage', 'sort_order'])
            ->withTimestamps();
    }

    public function mediaByStage(MediaStage $stage): BelongsToMany
    {
        return $this->media()->wherePivot('stage', $stage->value)->orderByPivot('sort_order');
    }

    /**
     * Same data as media(), exposed as HasMany so Filament's Repeater can
     * manage it (Repeater's relationship() integration needs HasMany/
     * MorphMany, not BelongsToMany - see ProjectMedia).
     */
    public function projectMedia(): HasMany
    {
        return $this->hasMany(ProjectMedia::class)->orderBy('sort_order');
    }
}
