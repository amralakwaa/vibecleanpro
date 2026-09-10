<?php

namespace Database\Factories;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => PageType::Landing,
            'title' => fake()->sentence(3),
            'slug' => fake()->unique()->slug(3),
            'status' => PageStatus::Draft,
            'published_at' => null,
            'sort_order' => 0,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => PageStatus::Published,
            'published_at' => now(),
        ]);
    }
}
