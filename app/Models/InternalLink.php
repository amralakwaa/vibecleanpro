<?php

namespace App\Models;

use Database\Factories\InternalLinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['anchor_text', 'context', 'sort_order'])]
class InternalLink extends Model
{
    /** @use HasFactory<InternalLinkFactory> */
    use HasFactory;

    public function fromPage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'from_page_id');
    }

    public function toPage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'to_page_id');
    }
}
