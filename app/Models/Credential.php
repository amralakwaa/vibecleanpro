<?php

namespace App\Models;

use App\Enums\CredentialStatus;
use App\Enums\CredentialType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A credential in the trust system: an internal Vibe Clean Pro standard
 * (VCP-*), internal training, or an external licence/certification.
 *
 * The honesty contract lives in scopePublic(): a row reaches a visitor ONLY
 * when its status is publicly allowed (Active for internal, Verified for
 * external) AND is_public is on. Planned/Pending rows (the ISO & licence
 * roadmap) are admin-only and can never leak to the public.
 */
class Credential extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'credential_type' => CredentialType::class,
            'status' => CredentialStatus::class,
            'issued_at' => 'date',
            'expires_at' => 'date',
            'review_at' => 'date',
            'is_internal' => 'boolean',
            'is_public' => 'boolean',
        ];
    }

    public function documentMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'document_media_id');
    }

    public function logoMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'logo_media_id');
    }

    /**
     * The only rows allowed in front of a visitor: a publicly-allowed status
     * (Active / Verified) AND the is_public toggle on. Everything on the
     * roadmap (planned/pending) and every hidden/expired/suspended row is
     * excluded here, so a public view can never show an unheld credential.
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query
            ->where('is_public', true)
            ->whereIn('status', [CredentialStatus::Active->value, CredentialStatus::Verified->value]);
    }

    public function scopeInternalStandards(Builder $query): Builder
    {
        return $query->where('credential_type', CredentialType::InternalStandard->value);
    }

    /** External (non-internal) credentials: licences, certifications, etc. */
    public function scopeExternal(Builder $query): Builder
    {
        return $query->where('is_internal', false);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function isPubliclyVisible(): bool
    {
        return $this->is_public && $this->status->isPubliclyAllowed();
    }

    public function isExpiringSoon(int $days = 45): bool
    {
        return $this->expires_at !== null
            && $this->expires_at->isFuture()
            && $this->expires_at->isBefore(now()->addDays($days));
    }

    /**
     * The internal verification URL for a document code (VCP-QMS-001). Null for
     * a credential without an internal code (an external one verifies through
     * its issuer's own verification_url instead).
     */
    public function verifyUrl(): ?string
    {
        return $this->document_code
            ? route('public.trust.verify', ['code' => $this->document_code])
            : null;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
