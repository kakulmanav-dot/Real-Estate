<?php

namespace App\Http\Requests\Property;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['sometimes', 'required', 'string'],
            'purpose' => ['sometimes', 'required', 'in:sale,rent'],
            'property_type' => ['sometimes', 'required', 'string', 'max:100'],
            'status' => ['nullable', 'in:draft,published,sold,rented,archived'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'price_period' => ['nullable', 'string', 'max:50'],
            'address' => ['sometimes', 'required', 'string', 'max:255'],
            'city' => ['sometimes', 'required', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'bedrooms' => ['sometimes', 'required', 'integer', 'min:0'],
            'bathrooms' => ['sometimes', 'required', 'integer', 'min:0'],
            'balconies' => ['nullable', 'integer', 'min:0'],
            'parking_spaces' => ['nullable', 'integer', 'min:0'],
            'area' => ['sometimes', 'required', 'numeric', 'min:0'],
            'area_unit' => ['nullable', 'string', 'max:20'],
            'furnishing_status' => ['nullable', 'string', 'max:50'],
            'year_built' => ['nullable', 'integer', 'min:1800', 'max:'.(date('Y') + 1)],
            'featured' => ['nullable', 'boolean'],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['string', 'max:100'],
        ];
    }
}
