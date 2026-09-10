<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['page_id', 'slug'])]
class SlugHistory extends Model
{
    const UPDATED_AT = null;

    protected $table = 'slug_history';

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
