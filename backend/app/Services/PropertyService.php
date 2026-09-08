<?php

namespace App\Services;

use App\Enums\PropertyStatus;
use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PropertyService
{
    public function create(array $data, int $userId, array $images = []): Property
    {
        return DB::transaction(function () use ($data, $userId, $images) {
            $data['created_by'] = $userId;
            $data['status'] ??= PropertyStatus::Draft->value;

            if ($data['status'] === PropertyStatus::Published->value) {
                $data['published_at'] = now();
            }

            $property = Property::create($data);

            $this->storeImages($property, $images);

            return $property->fresh(['images']);
        });
    }

    public function update(Property $property, array $data): Property
    {
        return DB::transaction(function () use ($property, $data) {
            if (isset($data['status'])) {
                if ($data['status'] === PropertyStatus::Published->value && $property->status !== PropertyStatus::Published) {
                    $data['published_at'] = now();
                } elseif ($data['status'] !== PropertyStatus::Published->value) {
                    $data['published_at'] = null;
                }
            }

            $property->update($data);

            return $property->fresh(['images']);
        });
    }

    public function setStatus(Property $property, string $status): Property
    {
        return $this->update($property, ['status' => $status]);
    }

    public function setFeatured(Property $property, bool $featured): Property
    {
        $property->update(['featured' => $featured]);

        return $property->fresh();
    }

    /**
     * @param  UploadedFile[]  $images
     */
    public function storeImages(Property $property, array $images): void
    {
        if (empty($images)) {
            return;
        }

        $hasCover = $property->images()->where('is_cover', true)->exists();
        $startOrder = (int) $property->images()->max('sort_order');

        foreach ($images as $index => $file) {
            $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('properties/'.$property->id, $filename, 'public');

            PropertyImage::create([
                'property_id' => $property->id,
                'image_path' => $path,
                'sort_order' => $startOrder + $index + 1,
                'is_cover' => ! $hasCover && $index === 0,
            ]);

            if (! $hasCover && $index === 0) {
                $hasCover = true;
            }
        }
    }

    public function setCoverImage(Property $property, PropertyImage $image): void
    {
        DB::transaction(function () use ($property, $image) {
            $property->images()->update(['is_cover' => false]);
            $image->update(['is_cover' => true]);
        });
    }

    public function reorderImages(Property $property, array $orderedIds): void
    {
        DB::transaction(function () use ($property, $orderedIds) {
            foreach ($orderedIds as $index => $imageId) {
                PropertyImage::where('id', $imageId)
                    ->where('property_id', $property->id)
                    ->update(['sort_order' => $index + 1]);
            }
        });
    }

    public function deleteImage(PropertyImage $image): void
    {
        Storage::disk('public')->delete($image->image_path);
        $image->delete();
    }
}
