<?php

namespace App\Enums;

/**
 * What kind of credential a row in the trust system is.
 *
 * The first, InternalStandard, is a standard Vibe Clean Pro writes and adopts
 * for its own operation (VCP-QMS-001, VCP-SHS-001, ...). It is NEVER an ISO or
 * a government accreditation and must always read as "معيار داخلي". The rest
 * describe EXTERNAL things issued by someone else (a ministry, a certification
 * body, a chamber); those are only shown to the public once a real document is
 * in hand (status Verified) - until then they live in the admin roadmap as
 * Planned/Pending. See CredentialStatus for the visibility rules.
 */
enum CredentialType: string
{
    case InternalStandard = 'internal_standard';
    case Training = 'training';
    case License = 'license';
    case Certification = 'certification';
    case Accreditation = 'accreditation';
    case Registration = 'registration';
    case Permit = 'permit';
    case Membership = 'membership';
    case Compliance = 'compliance';

    public function label(): string
    {
        return match ($this) {
            self::InternalStandard => 'معيار داخلي',
            self::Training => 'تدريب داخلي',
            self::License => 'رخصة',
            self::Certification => 'شهادة',
            self::Accreditation => 'اعتماد',
            self::Registration => 'تسجيل',
            self::Permit => 'تصريح',
            self::Membership => 'عضوية',
            self::Compliance => 'امتثال',
        };
    }

    /**
     * Issued by Vibe Clean Pro itself (internal standards and internal
     * training), as opposed to an external body. Internal credentials may be
     * shown publicly the moment they are Active; external ones only once
     * Verified. This is a default - the `is_internal` column is authoritative
     * per row - but it keeps new rows honest.
     */
    public function isInternalByDefault(): bool
    {
        return $this === self::InternalStandard || $this === self::Training;
    }
}
