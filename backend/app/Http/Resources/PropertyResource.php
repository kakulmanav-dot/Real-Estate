<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Property */
class PropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'reference_number' => $this->reference_number,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'purpose' => $this->purpose->value,
            'property_type' => $this->property_type,
            'status' => $this->status->value,
            'price' => (float) $this->price,
            'currency' => $this->currency,
            'price_period' => $this->price_period,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postal_code' => $this->postal_code,
            'latitude' => $this->latitude ? (float) $this->latitude : null,
            'longitude' => $this->longitude ? (float) $this->longitude : null,
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'balconies' => $this->balconies,
            'parking_spaces' => $this->parking_spaces,
            'area' => (float) $this->area,
            'area_unit' => $this->area_unit,
            'furnishing_status' => $this->furnishing_status,
            'year_built' => $this->year_built,
            'featured' => $this->featured,
            'amenities' => $this->amenities ?? [],
            'images' => PropertyImageResource::collection($this->whenLoaded('images')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->when($this->trashed(), $this->deleted_at),
        ];
    }
}
