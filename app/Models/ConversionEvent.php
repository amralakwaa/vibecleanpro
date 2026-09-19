<?php

namespace App\Models;

use App\Enums\ConversionEventType;
use Database\Factories\ConversionEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'event_type', 'page_id', 'page_path', 'service_id', 'area_id', 'lead_id',
    'source', 'utm_campaign', 'device_type', 'session_hash', 'created_at',
])]
class ConversionEvent extends Model
{
    /** @use HasFactory<ConversionEventFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'event_type' => ConversionEventType::class,
            'created_at' => 'datetime',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
