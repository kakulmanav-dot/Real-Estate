<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Enquiry */
class EnquiryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'subject' => $this->subject,
            'message' => $this->message,
            'source' => $this->source,
            'status' => $this->status->value,
            'admin_notes' => $this->admin_notes,
            'property' => $this->whenLoaded('property', fn () => [
                'id' => $this->property->id,
                'title' => $this->property->title,
                'slug' => $this->property->slug,
            ]),
            'assignee' => new UserResource($this->whenLoaded('assignee')),
            'ip_address' => $this->ip_address,
            'created_at' => $this->created_at,
        ];
    }
}
