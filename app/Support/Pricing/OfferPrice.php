<?php

namespace App\Support\Pricing;

use App\Models\Offer;
use App\Models\Service;
use Illuminate\Support\Collection;

/**
 * What an offer may say about money. The offer's own price is a number
 * the admin typed; the "instead of" price is never derived - it exists
 * only when the offer covers exactly one service whose public price is
 * a single real number (fixed or starting-from) above the offer price.
 */
final readonly class OfferPrice
{
    private function __construct(
        public float $price,
        public ?float $before,
    ) {}

    /**
     * @param  Collection<int, Service>  $services  The offer's linked services, already loaded.
     */
    public static function forOffer(Offer $offer, Collection $services): ?self
    {
        if ($offer->offer_price === null) {
            return null;
        }

        $price = (float) $offer->offer_price;
        $before = null;

        if ($services->count() === 1) {
            $base = $services->first()->publicPrice();

            if ($base && $base->max === null && $base->unit === null && $base->min > $price) {
                $before = $base->min;
            }
        }

        return new self($price, $before);
    }

    public function label(): string
    {
        return PublicPrice::format($this->price);
    }

    public function beforeLabel(): ?string
    {
        return $this->before !== null ? PublicPrice::format($this->before) : null;
    }
}
