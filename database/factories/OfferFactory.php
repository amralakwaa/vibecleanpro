<?php

namespace Database\Factories;

use App\Models\Offer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offer>
 */
class OfferFactory extends Factory
{
    public function definition(): array
    {
        return [
            'featured_media_id' => null,
            'title' => fake()->sentence(3),
            'discount_label' => fake()->numberBetween(10, 40).'% خصم',
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addMonth()->toDateString(),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
