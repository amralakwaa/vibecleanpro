<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'service_category_id' => null,
            'name' => fake()->unique()->words(2, true),
            'short_description' => fake()->sentence(),
            'icon' => 'heroicon-o-sparkles',
            'is_featured' => false,
            'sort_order' => 0,
        ];
    }
}
