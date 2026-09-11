<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'name', 'phone', 'whatsapp_number', 'email', 'address', 'city',
    'latitude', 'longitude', 'working_hours', 'social_links', 'logo_media_id',
])]
class BusinessProfile extends Model
{
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'working_hours' => 'array',
            'social_links' => 'array',
        ];
    }

    public function logo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'logo_media_id');
    }

    /**
     * wa.me only accepts digits - strips everything else (spaces, +, -)
     * from whatever format an editor typed the number in.
     */
    public function whatsappUrl(): ?string
    {
        return $this->whatsapp_number
            ? 'https://wa.me/'.preg_replace('/\D/', '', $this->whatsapp_number)
            : null;
    }

    public function phoneUrl(): ?string
    {
        return $this->phone
            ? 'tel:'.preg_replace('/[^0-9+]/', '', $this->phone)
            : null;
    }
}
