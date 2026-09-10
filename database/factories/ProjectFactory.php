<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'area_id' => null,
            'title' => fake()->sentence(3),
            'summary' => fake()->sentence(),
            'completed_at' => fake()->date(),
            'is_featured' => false,
            'sort_order' => 0,
        ];
    }
}
