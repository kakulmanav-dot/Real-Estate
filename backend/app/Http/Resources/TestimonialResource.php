<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\Testimonial */
class TestimonialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'designation' => $this->designation,
            'text' => $this->text,
            'rating' => $this->rating,
            'image_url' => $this->image_path ? Storage::disk('public')->url($this->image_path) : null,
            'is_approved' => $this->is_approved,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at,
        ];
    }
}
