<?php

namespace App\Models;

use Database\Factories\TeamMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'role_title', 'photo_media_id', 'bio', 'sort_order', 'is_active'])]
class TeamMember extends Model
{
    /** @use HasFactory<TeamMemberFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'photo_media_id');
    }

    /**
     * The only public read of this table: visible members in display order.
     */
    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order');
    }
}
