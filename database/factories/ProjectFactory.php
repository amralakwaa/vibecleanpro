<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * The default is a project the owner has confirmed (site, date, publication
     * permission) - the only kind that may be published.
     */
    public function definition(): array
    {
        return [
            'area_id' => null,
            'title' => fake()->sentence(3),
            'summary' => fake()->sentence(),
            'completed_at' => fake()->date(),
            'owner_confirmed_at' => now(),
            'is_featured' => false,
            'sort_order' => 0,
        ];
    }

    public function unconfirmed(): static
    {
        return $this->state(['owner_confirmed_at' => null, 'completed_at' => null, 'summary' => null]);
    }
}
