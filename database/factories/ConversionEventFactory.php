<?php

namespace Database\Factories;

use App\Enums\ConversionEventType;
use App\Models\ConversionEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConversionEvent>
 */
class ConversionEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_type' => fake()->randomElement(ConversionEventType::cases()),
            'page_path' => '/',
            'source' => 'direct',
            'device_type' => fake()->randomElement(['mobile', 'tablet', 'desktop']),
            'session_hash' => hash('sha256', fake()->uuid()),
        ];
    }
}
