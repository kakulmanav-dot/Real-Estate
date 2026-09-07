<?php

namespace Database\Factories;

use App\Enums\PropertyPurpose;
use App\Enums\PropertyStatus;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->streetName().' '.fake()->randomElement(['Residence', 'Villa', 'Heights', 'Estate', 'Suites']);
        $purpose = fake()->randomElement(PropertyPurpose::cases());

        return [
            'title' => $title,
            'short_description' => fake()->sentence(12),
            'description' => fake()->paragraphs(4, true),
            'purpose' => $purpose,
            'property_type' => fake()->randomElement(['Apartment', 'Villa', 'Townhouse', 'Penthouse', 'Studio']),
            'status' => PropertyStatus::Published,
            'price' => fake()->numberBetween(80_000, 4_500_000),
            'currency' => 'USD',
            'price_period' => $purpose === PropertyPurpose::Rent ? 'month' : null,
            'address' => fake()->streetAddress(),
            'city' => fake()->randomElement(['California', 'San Francisco', 'Chicago', 'Los Angeles', 'New York', 'Austin']),
            'state' => fake()->stateAbbr(),
            'country' => 'United States',
            'postal_code' => fake()->postcode(),
            'latitude' => fake()->latitude(24, 49),
            'longitude' => fake()->longitude(-124, -67),
            'bedrooms' => fake()->numberBetween(1, 6),
            'bathrooms' => fake()->numberBetween(1, 5),
            'balconies' => fake()->numberBetween(0, 3),
            'parking_spaces' => fake()->numberBetween(0, 3),
            'area' => fake()->numberBetween(600, 6000),
            'area_unit' => 'sqft',
            'furnishing_status' => fake()->randomElement(['Unfurnished', 'Semi-Furnished', 'Fully Furnished']),
            'year_built' => fake()->numberBetween(1990, 2025),
            'featured' => fake()->boolean(30),
            'amenities' => fake()->randomElements(
                ['Swimming Pool', 'Gym', 'Parking', 'Garden', 'Security', '24/7 Power Backup', 'Elevator', 'Clubhouse'],
                fake()->numberBetween(2, 5)
            ),
            'created_by' => User::factory()->admin(),
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => PropertyStatus::Draft,
            'published_at' => null,
        ]);
    }
}
