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
        $name = fake()->unique()->citySuffix().' '.fake()->unique()->numberBetween(1, 100000);

        return [
            'area_group_id' => null,
            'name' => $name,
            'slug' => Str::slug($name),
            'sort_order' => 0,
        ];
    }
}
