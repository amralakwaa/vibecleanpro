<?php

namespace App\Enums;

/**
 * Lifecycle state of a credential, and the single source for whether it may
 * face the public.
 *
 * Public display rule (the project's honesty contract):
 *  - Active:    an internal Vibe Clean Pro standard that has been adopted  -> public
 *  - Verified:  an external credential whose real document is in hand      -> public
 *  - Pending / Planned / Expired / Suspended / Hidden                      -> never public
 *
 * Planned/Pending are the admin-only roadmap: an ISO or a licence the company
 * intends to obtain is recorded here so the work is tracked, but it is NEVER
 * shown as if already held.
 */
enum CredentialStatus: string
{
    case Active = 'active';
    case Verified = 'verified';
    case Pending = 'pending';
    case Planned = 'planned';
    case Expired = 'expired';
    case Suspended = 'suspended';
    case Hidden = 'hidden';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'مُعتمد داخليًا',
            self::Verified => 'موثّق',
            self::Pending => 'قيد الإصدار',
            self::Planned => 'مخطّط',
            self::Expired => 'منتهٍ',
            self::Suspended => 'موقوف',
            self::Hidden => 'مخفي',
        };
    }

    /**
     * Whether this status is ever allowed in front of a visitor. A row must
     * also have is_public = true to actually appear; this is the status gate.
     */
    public function isPubliclyAllowed(): bool
    {
        return $this === self::Active || $this === self::Verified;
    }

    /**
     * Filament badge colour.
     */
    public function color(): string
    {
        return match ($this) {
            self::Active, self::Verified => 'success',
            self::Pending => 'warning',
            self::Planned => 'info',
            self::Expired, self::Suspended => 'danger',
            self::Hidden => 'gray',
        };
    }
}
