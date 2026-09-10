<?php

namespace Database\Factories;

use App\Models\ContentBlock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentBlock>
 */
class ContentBlockFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => 'rich_text',
            'data' => ['content' => fake()->paragraph()],
            'position' => 0,
            'is_active' => true,
        ];
    }
}
