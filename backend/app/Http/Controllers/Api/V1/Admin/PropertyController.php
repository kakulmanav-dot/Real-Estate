<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Property\PropertyIndexRequest;
use App\Http\Requests\Property\StorePropertyRequest;
use App\Http\Requests\Property\UpdatePropertyRequest;
use App\Http\Resources\PropertyListResource;
use App\Http\Resources\PropertyResource;
use App\Models\ActivityLog;
use App\Models\Property;
use App\Services\PropertyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PropertyController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly PropertyService $propertyService) {}

    public function index(PropertyIndexRequest $request)
    {
        Gate::authorize('viewAny', Property::class);

        $perPage = $request->integer('per_page', 15);

        $query = Property::query()->withTrashed()->with('images');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $properties = $query
            ->search($request->string('search')->toString())
            ->filter($request->only(['purpose', 'property_type', 'city', 'bedrooms', 'min_price', 'max_price', 'min_area', 'max_area']))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return $this->success(
            PropertyListResource::collection($properties->items()),
            'Properties retrieved successfully.',
            200,
            [
                'current_page' => $properties->currentPage(),
                'per_page' => $properties->perPage(),
                'total' => $properties->total(),
                'last_page' => $properties->lastPage(),
            ]
        );
    }

    public function store(StorePropertyRequest $request)
    {
        Gate::authorize('create', Property::class);

        $data = $request->safe()->except(['images']);
        $property = $this->propertyService->create($data, $request->user()->id, $request->file('images', []));

        ActivityLog::record('property.created', 'Property', $property->id, ['title' => $property->title]);

        return $this->success(new PropertyResource($property), 'Property created successfully.', 201);
    }

    public function show(Property $property)
    {
        Gate::authorize('view', $property);

        $property->load(['images', 'creator']);

        return $this->success(new PropertyResource($property), 'Property retrieved successfully.');
    }

    public function update(UpdatePropertyRequest $request, Property $property)
    {
        Gate::authorize('update', $property);

        $property = $this->propertyService->update($property, $request->validated());

        ActivityLog::record('property.updated', 'Property', $property->id, ['title' => $property->title]);

        return $this->success(new PropertyResource($property), 'Property updated successfully.');
    }

    public function destroy(Property $property)
    {
        Gate::authorize('delete', $property);

        $property->delete();

        ActivityLog::record('property.deleted', 'Property', $property->id, ['title' => $property->title]);

        return $this->success(null, 'Property deleted successfully.');
    }

    public function restore(int $id)
    {
        $property = Property::withTrashed()->findOrFail($id);
        Gate::authorize('restore', $property);

        $property->restore();

        ActivityLog::record('property.restored', 'Property', $property->id, ['title' => $property->title]);

        return $this->success(new PropertyResource($property), 'Property restored successfully.');
    }

    public function forceDestroy(int $id)
    {
        $property = Property::withTrashed()->findOrFail($id);
        Gate::authorize('forceDelete', $property);

        foreach ($property->images as $image) {
            $this->propertyService->deleteImage($image);
        }

        $title = $property->title;
        $property->forceDelete();

        ActivityLog::record('property.force_deleted', 'Property', $id, ['title' => $title]);

        return $this->success(null, 'Property permanently deleted.');
    }

    public function updateStatus(Request $request, Property $property)
    {
        Gate::authorize('update', $property);

        $request->validate([
            'status' => ['required', 'in:draft,published,sold,rented,archived'],
        ]);

        $property = $this->propertyService->setStatus($property, $request->input('status'));

        ActivityLog::record('property.status_changed', 'Property', $property->id, ['status' => $property->status->value]);

        return $this->success(new PropertyResource($property), 'Property status updated successfully.');
    }

    public function updateFeatured(Request $request, Property $property)
    {
        Gate::authorize('update', $property);

        $request->validate(['featured' => ['required', 'boolean']]);

        $property = $this->propertyService->setFeatured($property, $request->boolean('featured'));

        ActivityLog::record('property.featured_changed', 'Property', $property->id, ['featured' => $property->featured]);

        return $this->success(new PropertyResource($property), 'Property featured status updated successfully.');
    }
}
