<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\EnquiryStatus;
use App\Enums\PropertyStatus;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\EnquiryResource;
use App\Http\Resources\PropertyListResource;
use App\Models\Enquiry;
use App\Models\Property;
use App\Models\User;

class DashboardController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $stats = [
            'total_properties' => Property::withTrashed()->count(),
            'published_properties' => Property::where('status', PropertyStatus::Published)->count(),
            'draft_properties' => Property::where('status', PropertyStatus::Draft)->count(),
            'featured_properties' => Property::where('featured', true)->count(),
            'sold_properties' => Property::where('status', PropertyStatus::Sold)->count(),
            'rented_properties' => Property::where('status', PropertyStatus::Rented)->count(),
            'total_enquiries' => Enquiry::count(),
            'new_enquiries' => Enquiry::where('status', EnquiryStatus::New)->count(),
            'total_users' => User::count(),
        ];

        $recentEnquiries = Enquiry::with('property')->latest()->limit(5)->get();
        $recentProperties = Property::with('images')->latest()->limit(5)->get();

        return $this->success([
            'stats' => $stats,
            'recent_enquiries' => EnquiryResource::collection($recentEnquiries),
            'recent_properties' => PropertyListResource::collection($recentProperties),
        ], 'Dashboard data retrieved successfully.');
    }
}
