<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\Property */
class PropertyListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $cover = $this->images->firstWhere('is_cover', true) ?? $this->images->first();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'reference_number' => $this->reference_number,
            'short_description' => $this->short_description,
            'purpose' => $this->purpose->value,
            'property_type' => $this->property_type,
            'status' => $this->status->value,
            'price' => (float) $this->price,
            'currency' => $this->currency,
            'price_period' => $this->price_period,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'area' => (float) $this->area,
            'area_unit' => $this->area_unit,
            'featured' => $this->featured,
            'cover_image_url' => $cover ? Storage::disk('public')->url($cover->image_path) : null,
            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
            'deleted_at' => $this->when($this->trashed(), $this->deleted_at),
        ];
    }
}
