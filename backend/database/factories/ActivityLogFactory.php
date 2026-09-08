<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->admin(),
            'action' => fake()->randomElement(['property.created', 'property.updated', 'settings.updated']),
            'entity_type' => 'Property',
            'entity_id' => fake()->numberBetween(1, 1000),
            'metadata' => [],
            'ip_address' => fake()->ipv4(),
        ];
    }
}
