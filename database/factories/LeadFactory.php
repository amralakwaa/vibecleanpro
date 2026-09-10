<?php

namespace Database\Factories;

use App\Enums\LeadStatus;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'source_page_id' => null,
            'service_id' => null,
            'area_id' => null,
            'name' => fake()->name(),
            'phone' => fake()->numerify('05########'),
            'email' => fake()->safeEmail(),
            'message' => fake()->sentence(),
            'status' => LeadStatus::New,
            'ip_address' => fake()->ipv4(),
            'user_agent' => 'Mozilla/5.0',
        ];
    }
}
