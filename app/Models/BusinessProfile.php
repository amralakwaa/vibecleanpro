<?php

namespace App\Models;

use App\Support\Settings\DemoValue;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'name', 'phone', 'whatsapp_number', 'email', 'lead_notification_email', 'address', 'city',
    'latitude', 'longitude', 'working_hours', 'social_links', 'logo_media_id',
    'tagline', 'identity_statement', 'story', 'mission', 'vision', 'values',
    'founder_name', 'founder_title', 'founder_photo_media_id', 'founder_bio', 'founder_long_bio',
    'show_founder', 'show_team', 'trust_points',
    'commercial_registration_number', 'display_commercial_registration', 'service_area',
    'google_business_profile_url', 'google_review_url', 'google_maps_place_id',
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
            'values' => 'array',
            'trust_points' => 'array',
            'show_founder' => 'boolean',
            'show_team' => 'boolean',
            'display_commercial_registration' => 'boolean',
        ];
    }

    /**
     * The registration number shown publicly - only a real, entered number
     * the owner chose to display.
     */
    public function publicCommercialRegistration(): ?string
    {
        return $this->display_commercial_registration
            ? DemoValue::realOrNull($this->commercial_registration_number)
            : null;
    }

    /**
     * Google links are shown publicly only when they are real: a seeded
     * placeholder (example.invalid) never reaches a visitor.
     */
    public function publicGoogleReviewUrl(): ?string
    {
        return DemoValue::realOrNull($this->google_review_url);
    }

    public function publicGoogleBusinessProfileUrl(): ?string
    {
        return DemoValue::realOrNull($this->google_business_profile_url);
    }

    public function logo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'logo_media_id');
    }

    public function founderPhoto(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'founder_photo_media_id');
    }

    /**
     * The founder section exists on the public site only when the editor
     * both entered a name and left the section switched on.
     */
    public function hasVisibleFounder(): bool
    {
        return $this->show_founder && filled($this->founder_name);
    }

    /**
     * wa.me only accepts digits - strips everything else (spaces, +, -)
     * from whatever format an editor typed the number in. $message, when
     * given, is a short prefilled greeting (e.g. "مرحبًا، أرغب في
     * الاستفسار عن خدمة تنظيف السجاد") - always urlencoded here so no
     * caller has to remember to do it.
     */
    public function whatsappUrl(?string $message = null): ?string
    {
        if (! $this->whatsapp_number) {
            return null;
        }

        $url = 'https://wa.me/'.preg_replace('/\D/', '', $this->whatsapp_number);

        return $message ? $url.'?text='.rawurlencode($message) : $url;
    }

    public function phoneUrl(): ?string
    {
        return $this->phone
            ? 'tel:'.preg_replace('/[^0-9+]/', '', $this->phone)
            : null;
    }
}
