<?php

namespace App\Models;

use App\Enums\MediaStage;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * A real pivot model (not just a plain belongsToMany) so Filament's Repeater
 * can manage before/during/after project photos as a HasMany relationship -
 * Repeater only integrates cleanly with HasMany/MorphMany, not BelongsToMany.
 */
#[Fillable(['media_id', 'stage', 'sort_order'])]
class ProjectMedia extends Pivot
{
    public $incrementing = true;

    protected $table = 'project_media';

    protected function casts(): array
    {
        return [
            'stage' => MediaStage::class,
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
