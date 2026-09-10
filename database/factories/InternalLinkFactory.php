<?php

namespace Database\Factories;

use App\Models\InternalLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InternalLink>
 */
class InternalLinkFactory extends Factory
{
    public function definition(): array
    {
        return [
            'anchor_text' => fake()->words(3, true),
            'context' => 'related',
            'sort_order' => 0,
        ];
    }
}
