<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        $testimonials = [
            [
                'name' => 'Donald Jackman',
                'designation' => 'Marketing Manager',
                'text' => 'From the very first meeting, they understood my vision and helped me find the perfect property. Their attention to detail and commitment to client satisfaction is unmatched.',
                'rating' => 5,
                'seed_image' => 'profile_img_1.png',
                'sort_order' => 1,
            ],
            [
                'name' => 'Richard Nelson',
                'designation' => 'UI/UX Designer',
                'text' => 'From the very first meeting, they understood my vision and helped me find the perfect property. Their attention to detail and commitment to client satisfaction is unmatched.',
                'rating' => 4,
                'seed_image' => 'profile_img_2.png',
                'sort_order' => 2,
            ],
            [
                'name' => 'James Washington',
                'designation' => 'Co-Founder',
                'text' => 'From the very first meeting, they understood my vision and helped me find the perfect property. Their attention to detail and commitment to client satisfaction is unmatched.',
                'rating' => 5,
                'seed_image' => 'profile_img_3.png',
                'sort_order' => 3,
            ],
        ];

        foreach ($testimonials as $data) {
            $imagePath = null;
            $seedFile = 'seed/testimonials/'.$data['seed_image'];

            if (Storage::disk('public')->exists($seedFile)) {
                $imagePath = 'testimonials/'.$data['seed_image'];
                if (! Storage::disk('public')->exists($imagePath)) {
                    Storage::disk('public')->copy($seedFile, $imagePath);
                }
            }

            Testimonial::updateOrCreate(
                ['name' => $data['name'], 'designation' => $data['designation']],
                [
                    'text' => $data['text'],
                    'rating' => $data['rating'],
                    'image_path' => $imagePath,
                    'is_approved' => true,
                    'sort_order' => $data['sort_order'],
                ]
            );
        }

        $this->command?->info('Testimonials seeded.');
    }
}
