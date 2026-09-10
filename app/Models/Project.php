<?php

namespace App\Models;

use App\Enums\MediaStage;
use App\Models\Concerns\HasPage;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['area_id', 'title', 'summary', 'completed_at', 'is_featured', 'sort_order'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, HasPage, SoftDeletes;

    protected function casts(): array
    {
        return [
            'completed_at' => 'date',
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
}
