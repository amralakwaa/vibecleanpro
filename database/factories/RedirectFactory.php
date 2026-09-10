<?php

namespace Database\Factories;

use App\Models\Redirect;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Redirect>
 */
class RedirectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'from_path' => '/'.fake()->unique()->slug(2),
            'to_path' => '/'.fake()->slug(2),
            'type' => 301,
            'is_active' => true,
            'hits' => 0,
        ];
    }
}
