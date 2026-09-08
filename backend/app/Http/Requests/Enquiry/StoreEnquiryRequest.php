<?php

namespace App\Http\Requests\Enquiry;

use Illuminate\Foundation\Http\FormRequest;

class StoreEnquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'property_id' => ['nullable', 'integer', 'exists:properties,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'source' => ['nullable', 'string', 'max:100'],
            // Honeypot field: real users never see or fill this. Deliberately
            // unconstrained here — rejecting it via a validation rule would
            // return a distinguishable error that tips off bots. The
            // controller silently accepts-but-discards a filled honeypot instead.
            'website' => ['nullable', 'string'],
        ];
    }
}
