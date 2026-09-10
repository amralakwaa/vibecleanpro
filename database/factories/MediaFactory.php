<?php

namespace Database\Factories;

use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'disk' => 'public',
            'path' => 'media/'.fake()->uuid().'.jpg',
            'original_filename' => fake()->word().'.jpg',
            'mime_type' => 'image/jpeg',
            'size' => fake()->numberBetween(10_000, 500_000),
            'width' => 1200,
            'height' => 800,
            'alt_text' => fake()->sentence(4),
            'caption' => null,
            'variants' => null,
        ];
    }
}
