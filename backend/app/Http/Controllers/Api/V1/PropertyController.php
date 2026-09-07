<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Property\PropertyIndexRequest;
use App\Http\Resources\PropertyListResource;
use App\Http\Resources\PropertyResource;
use App\Models\Property;

class PropertyController extends Controller
{
    use ApiResponse;

    public function index(PropertyIndexRequest $request)
    {
        $perPage = $request->integer('per_page', 12);

        $properties = Property::query()
            ->published()
            ->with('images')
            ->search($request->string('search')->toString())
            ->filter($request->only([
                'purpose', 'property_type', 'city', 'bedrooms',
                'min_price', 'max_price', 'min_area', 'max_area',
            ]))
            ->sort($request->input('sort'))
            ->paginate($perPage)
            ->withQueryString();

        return $this->success(
            PropertyListResource::collection($properties->items()),
            'Properties retrieved successfully.',
            200,
            $this->paginationMeta($properties)
        );
    }

    public function featured()
    {
        $properties = Property::query()
            ->published()
            ->featured()
            ->with('images')
            ->orderByDesc('published_at')
            ->limit(6)
            ->get();

        return $this->success(PropertyListResource::collection($properties), 'Featured properties retrieved successfully.');
    }

    public function show(string $slug)
    {
        $property = Property::query()
            ->published()
            ->with(['images', 'creator'])
            ->where('slug', $slug)
            ->firstOrFail();

        return $this->success(new PropertyResource($property), 'Property retrieved successfully.');
    }

    public function similar(string $slug)
    {
        $property = Property::query()->published()->where('slug', $slug)->firstOrFail();

        $similar = Property::query()
            ->published()
            ->where('id', '!=', $property->id)
            ->where(function ($query) use ($property) {
                $query->where('city', $property->city)
                    ->orWhere('property_type', $property->property_type);
            })
            ->with('images')
            ->limit(4)
            ->get();

        return $this->success(PropertyListResource::collection($similar), 'Similar properties retrieved successfully.');
    }

    private function paginationMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ];
    }
}
