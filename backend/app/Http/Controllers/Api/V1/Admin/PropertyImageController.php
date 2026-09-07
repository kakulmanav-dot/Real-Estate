<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Property\ReorderPropertyImagesRequest;
use App\Http\Requests\Property\UploadPropertyImagesRequest;
use App\Http\Resources\PropertyImageResource;
use App\Models\ActivityLog;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Services\PropertyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PropertyImageController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly PropertyService $propertyService) {}

    public function store(UploadPropertyImagesRequest $request, Property $property)
    {
        Gate::authorize('update', $property);

        $this->propertyService->storeImages($property, $request->file('images'));

        ActivityLog::record('property.images_uploaded', 'Property', $property->id, ['count' => count($request->file('images'))]);

        return $this->success(PropertyImageResource::collection($property->fresh()->images), 'Images uploaded successfully.', 201);
    }

    public function update(Request $request, Property $property, PropertyImage $image)
    {
        Gate::authorize('update', $property);
        abort_if($image->property_id !== $property->id, 404);

        $request->validate(['alt_text' => ['nullable', 'string', 'max:255']]);

        $image->update($request->only('alt_text'));

        return $this->success(new PropertyImageResource($image), 'Image updated successfully.');
    }

    public function setCover(Property $property, PropertyImage $image)
    {
        Gate::authorize('update', $property);
        abort_if($image->property_id !== $property->id, 404);

        $this->propertyService->setCoverImage($property, $image);

        ActivityLog::record('property.cover_image_changed', 'Property', $property->id, ['image_id' => $image->id]);

        return $this->success(PropertyImageResource::collection($property->fresh()->images), 'Cover image updated successfully.');
    }

    public function reorder(ReorderPropertyImagesRequest $request, Property $property)
    {
        Gate::authorize('update', $property);

        $this->propertyService->reorderImages($property, $request->input('order'));

        return $this->success(PropertyImageResource::collection($property->fresh()->images), 'Images reordered successfully.');
    }

    public function destroy(Property $property, PropertyImage $image)
    {
        Gate::authorize('update', $property);
        abort_if($image->property_id !== $property->id, 404);

        $this->propertyService->deleteImage($image);

        ActivityLog::record('property.image_deleted', 'Property', $property->id, ['image_id' => $image->id]);

        return $this->success(null, 'Image deleted successfully.');
    }
}
