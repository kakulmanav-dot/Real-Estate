<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PropertyStatus;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyListResource;
use App\Models\Favorite;
use App\Models\Property;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 12);

        $properties = Property::query()
            ->published()
            ->with('images')
            ->whereHas('favoritedBy', fn ($q) => $q->where('user_id', $request->user()->id))
            ->paginate($perPage);

        return $this->success(
            PropertyListResource::collection($properties->items()),
            'Saved properties retrieved successfully.',
            200,
            [
                'current_page' => $properties->currentPage(),
                'per_page' => $properties->perPage(),
                'total' => $properties->total(),
                'last_page' => $properties->lastPage(),
            ]
        );
    }

    public function store(Request $request, Property $property)
    {
        abort_unless(
            $property->status === PropertyStatus::Published && $property->published_at !== null,
            404
        );

        $favorite = Favorite::firstOrCreate([
            'user_id' => $request->user()->id,
            'property_id' => $property->id,
        ]);

        return $this->success(['favorited' => true], $favorite->wasRecentlyCreated ? 'Property saved successfully.' : 'Property is already saved.', 201);
    }

    public function destroy(Request $request, Property $property)
    {
        Favorite::where('user_id', $request->user()->id)
            ->where('property_id', $property->id)
            ->delete();

        return $this->success(['favorited' => false], 'Property removed from saved list.');
    }
}
