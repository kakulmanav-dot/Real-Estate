<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            SettingSeeder::class,
            PropertySeeder::class,
            TestimonialSeeder::class,
        ]);

        if (app()->environment(['local', 'development'])) {
            $this->call(DevelopmentSeeder::class);
        }
    }
}
