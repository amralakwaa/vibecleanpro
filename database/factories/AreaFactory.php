<?php

namespace Database\Factories;

use App\Models\Area;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Area>
 */
class AreaFactory extends Factory
{
    public function definition(): array
    {
        // Only the trailing number needs to be unique (citySuffix() has too
        // small a pool - e.g. "town", "ville" - to stay unique()'d across a
        // large test run on its own; the number alone already guarantees a
        // unique name/slug).
        $name = fake()->citySuffix().' '.fake()->unique()->numberBetween(1, 1_000_000);

        return [
            'area_group_id' => null,
            'name' => $name,
            'slug' => Str::slug($name),
            'sort_order' => 0,
        ];
    }
}
