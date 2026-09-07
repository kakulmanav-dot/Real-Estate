<?php

namespace Database\Seeders;

use App\Enums\PropertyPurpose;
use App\Enums\PropertyStatus;
use App\Enums\UserRole;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PropertySeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', UserRole::Admin)->first()
            ?? User::factory()->admin()->create([
                'name' => 'Seed Administrator',
                'email' => 'seed-admin@realestate.test',
            ]);

        $properties = [
            [
                'title' => 'Skyline Haven',
                'short_description' => 'A modern residence with panoramic skyline views and resort-style amenities.',
                'city' => 'California',
                'state' => 'CA',
                'price' => 250000,
                'seed_image' => 'project_img_1.jpg',
                'bedrooms' => 3,
                'bathrooms' => 2,
                'area' => 1850,
            ],
            [
                'title' => 'Vista Verde',
                'short_description' => 'Contemporary living spaces surrounded by lush landscaped gardens.',
                'city' => 'San Francisco',
                'state' => 'CA',
                'price' => 250000,
                'seed_image' => 'project_img_2.jpg',
                'bedrooms' => 2,
                'bathrooms' => 2,
                'area' => 1400,
            ],
            [
                'title' => 'Serenity Suites',
                'short_description' => 'Elegant suites offering a calm retreat in the heart of the city.',
                'city' => 'Chicago',
                'state' => 'IL',
                'price' => 250000,
                'seed_image' => 'project_img_3.jpg',
                'bedrooms' => 3,
                'bathrooms' => 3,
                'area' => 2100,
            ],
            [
                'title' => 'Central Square',
                'short_description' => 'A prime address steps away from shopping, dining, and transit.',
                'city' => 'Los Angeles',
                'state' => 'CA',
                'price' => 250000,
                'seed_image' => 'project_img_4.jpg',
                'bedrooms' => 4,
                'bathrooms' => 3,
                'area' => 2600,
            ],
            [
                'title' => 'Vista Verde Heights',
                'short_description' => 'Elevated townhomes with private balconies and green courtyards.',
                'city' => 'San Francisco',
                'state' => 'CA',
                'price' => 275000,
                'seed_image' => 'project_img_5.jpg',
                'bedrooms' => 3,
                'bathrooms' => 2,
                'area' => 1900,
            ],
            [
                'title' => 'Serenity Suites East',
                'short_description' => 'A sister property to Serenity Suites with lakeside views.',
                'city' => 'Chicago',
                'state' => 'IL',
                'price' => 260000,
                'seed_image' => 'project_img_6.jpg',
                'bedrooms' => 2,
                'bathrooms' => 2,
                'area' => 1550,
            ],
        ];

        foreach ($properties as $data) {
            $slug = Str::slug($data['title']);

            $property = Property::withTrashed()->where('slug', $slug)->first();

            if (! $property) {
                $property = Property::create([
                    'title' => $data['title'],
                    'slug' => $slug,
                    'reference_number' => Property::generateReferenceNumber(),
                    'short_description' => $data['short_description'],
                    'description' => $data['short_description'].' This property features spacious interiors, modern finishes, and convenient access to major highways, schools, and shopping centers. Ideal for families and professionals seeking comfort and connectivity.',
                    'purpose' => PropertyPurpose::Sale,
                    'property_type' => 'Apartment',
                    'status' => PropertyStatus::Published,
                    'price' => $data['price'],
                    'currency' => 'USD',
                    'address' => '100 Main Street',
                    'city' => $data['city'],
                    'state' => $data['state'],
                    'country' => 'United States',
                    'postal_code' => '90001',
                    'bedrooms' => $data['bedrooms'],
                    'bathrooms' => $data['bathrooms'],
                    'balconies' => 1,
                    'parking_spaces' => 1,
                    'area' => $data['area'],
                    'area_unit' => 'sqft',
                    'furnishing_status' => 'Semi-Furnished',
                    'year_built' => 2020,
                    'featured' => true,
                    'amenities' => ['Swimming Pool', 'Gym', 'Parking', 'Security', '24/7 Power Backup'],
                    'created_by' => $admin->id,
                    'published_at' => now(),
                ]);
            }

            $seedFile = 'seed/properties/'.$data['seed_image'];

            if (Storage::disk('public')->exists($seedFile) && $property->images()->count() === 0) {
                $destination = 'properties/'.$property->id.'/'.$data['seed_image'];
                Storage::disk('public')->copy($seedFile, $destination);

                PropertyImage::create([
                    'property_id' => $property->id,
                    'image_path' => $destination,
                    'alt_text' => $data['title'],
                    'sort_order' => 1,
                    'is_cover' => true,
                ]);
            }
        }

        $this->command?->info('Properties seeded from the original frontend project cards.');
    }
}
