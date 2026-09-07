<?php

namespace Database\Seeders;

use App\Models\Enquiry;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Seeder;

class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('role', 'user')->count() < 5) {
            User::factory(5)->create();
        }

        if (Property::count() < 12) {
            Property::factory(10)->create();
        }

        if (Enquiry::count() === 0) {
            $properties = Property::inRandomOrder()->limit(5)->get();

            foreach ($properties as $property) {
                Enquiry::factory()->count(2)->create(['property_id' => $property->id]);
            }
        }

        $this->command?->info('Development sample data seeded.');
    }
}
