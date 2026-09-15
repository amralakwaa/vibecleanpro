<?php

namespace App\Support\Pricing;

use App\Enums\ServicePricingMode;
use App\Models\Service;

/**
 * The one place a service price is turned into words. Built only from
 * admin-entered numbers; returns null whenever there is nothing honest
 * to show (quote-only, hidden, or an incomplete record), so every caller
 * renders nothing rather than a placeholder.
 */
final readonly class PublicPrice
{
    private function __construct(
        public ServicePricingMode $mode,
        public float $min,
        public ?float $max,
        public ?string $unit,
        public ?string $note,
    ) {}

    public static function forService(Service $service): ?self
    {
        $mode = $service->pricing_mode;

        if (! $service->show_price || $mode === ServicePricingMode::QuoteOnly || $service->price_min === null) {
            return null;
        }

        if ($mode->requiresMax() && $service->price_max === null) {
            return null;
        }

        if ($mode->requiresUnit() && blank($service->price_unit)) {
            return null;
        }

        return new self($mode, (float) $service->price_min, $service->price_max !== null ? (float) $service->price_max : null, $service->price_unit, $service->price_note);
    }

    /** "يبدأ من 299 ر.س" / "399 ر.س" / "من 300 إلى 500 ر.س" / "20 ر.س / متر" */
    public function label(): string
    {
        return match ($this->mode) {
            ServicePricingMode::StartingFrom => 'يبدأ من '.self::format($this->min),
            ServicePricingMode::Fixed => self::format($this->min),
            ServicePricingMode::Range => 'من '.self::amount($this->min).' إلى '.self::format($this->max),
            ServicePricingMode::PerUnit => self::format($this->min).' / '.$this->unit,
            ServicePricingMode::QuoteOnly => '',
        };
    }

    /** The bare number with the currency, e.g. "1,500 ر.س". */
    public static function format(float $amount): string
    {
        return self::amount($amount).' '.config('pricing.symbol');
    }

    public static function amount(float $amount): string
    {
        return number_format($amount, floor($amount) === $amount ? 0 : 2);
    }
}
