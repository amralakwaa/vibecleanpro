<?php

namespace App\Enums;

enum MediaPrivacyStatus: string
{
    case Cleared = 'cleared';
    case HeldWorkerConsent = 'held_worker_consent';
    case HeldClientConsent = 'held_client_consent';
    case HeldThirdPartyBrand = 'held_third_party_brand';
    case Unverified = 'unverified';

    public function label(): string
    {
        return match ($this) {
            self::Cleared => 'مُراجَع — لا مانع',
            self::HeldWorkerConsent => 'محجوب — بانتظار موافقة العمال',
            self::HeldClientConsent => 'محجوب — بانتظار موافقة العميل',
            self::HeldThirdPartyBrand => 'محجوب — علامة تجارية لطرف ثالث',
            self::Unverified => 'غير مُراجَع',
        };
    }

    public function isHeld(): bool
    {
        return in_array($this, [self::HeldWorkerConsent, self::HeldClientConsent, self::HeldThirdPartyBrand], true);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $status) => [$status->value => $status->label()])->all();
    }
}
