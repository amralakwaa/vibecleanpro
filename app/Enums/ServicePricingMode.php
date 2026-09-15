<?php

namespace App\Enums;

enum ServicePricingMode: string
{
    case QuoteOnly = 'quote_only';
    case StartingFrom = 'starting_from';
    case Fixed = 'fixed';
    case Range = 'range';
    case PerUnit = 'per_unit';

    public function label(): string
    {
        return match ($this) {
            self::QuoteOnly => 'حسب الطلب (بدون سعر معلن)',
            self::StartingFrom => 'يبدأ من',
            self::Fixed => 'سعر ثابت',
            self::Range => 'نطاق سعري (من - إلى)',
            self::PerUnit => 'سعر لكل وحدة',
        };
    }

    public function requiresMin(): bool
    {
        return $this !== self::QuoteOnly;
    }

    public function requiresMax(): bool
    {
        return $this === self::Range;
    }

    public function requiresUnit(): bool
    {
        return $this === self::PerUnit;
    }
}
