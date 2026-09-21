<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One recorded change to a project's location fields.
 *
 * Written by ProjectObserver whenever a place field moves, never by hand.
 * It has no updated_at: a history row is a fact, not a record you edit.
 */
class ProjectLocationHistory extends Model
{
    public $timestamps = false;

    protected $table = 'project_location_history';

    protected $fillable = ['project_id', 'old_location', 'new_location', 'source', 'changed_by', 'created_at'];

    protected function casts(): array
    {
        return [
            'old_location' => 'array',
            'new_location' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
