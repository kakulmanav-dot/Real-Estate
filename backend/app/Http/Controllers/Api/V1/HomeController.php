<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyListResource;
use App\Http\Resources\TestimonialResource;
use App\Models\Property;
use App\Models\Setting;
use App\Models\Testimonial;

class HomeController extends Controller
{
    use ApiResponse;

    public function __invoke()
    {
        $featuredProperties = Property::query()
            ->published()
            ->featured()
            ->with('images')
            ->orderByDesc('published_at')
            ->limit(6)
            ->get();

        $testimonials = Testimonial::query()->approved()->get();

        return $this->success([
            'settings' => Setting::allSettings(),
            'featured_properties' => PropertyListResource::collection($featuredProperties),
            'testimonials' => TestimonialResource::collection($testimonials),
        ], 'Homepage data retrieved successfully.');
    }
}
