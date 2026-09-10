<?php

namespace Database\Factories;

use App\Models\SeoMetadata;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeoMetadata>
 */
class SeoMetadataFactory extends Factory
{
    public function definition(): array
    {
        return [
            'meta_title' => fake()->sentence(4),
            'meta_description' => fake()->sentence(12),
            'canonical_url' => null,
            'robots_index' => true,
            'robots_follow' => true,
            'og_title' => null,
            'og_description' => null,
            'structured_data' => null,
        ];
    }
}
