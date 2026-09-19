<?php

namespace App\Enums;

/**
 * Whether the company actually performs a service with its own team. A page
 * for a service it does not offer (or offers without a required licence)
 * is a false claim, so only Available may be published.
 */
enum ServiceCapability: string
{
    case Available = 'available';
    case NeedsConfirmation = 'needs_confirmation';
    case NotAvailable = 'not_available';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'متوفرة — ننفذها بفريقنا',
            self::NeedsConfirmation => 'بانتظار تأكيد المالك',
            self::NotAvailable => 'غير متوفرة',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $capability) => [$capability->value => $capability->label()])->all();
    }
}
