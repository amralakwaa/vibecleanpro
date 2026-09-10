<?php

namespace Database\Factories;

use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Testimonial>
 */
class TestimonialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'area_id' => null,
            'service_id' => null,
            'author_name' => fake()->name(),
            'rating' => fake()->numberBetween(3, 5),
            'content' => fake()->paragraph(),
            'is_featured' => false,
            'sort_order' => 0,
        ];
    }
}
