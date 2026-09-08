<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['sometimes', 'string', 'max:255'],
            'company_email' => ['sometimes', 'email', 'max:255'],
            'company_phone' => ['sometimes', 'string', 'max:50'],
            'company_address' => ['sometimes', 'string', 'max:500'],
            'hero_title' => ['sometimes', 'string', 'max:255'],
            'hero_subtitle' => ['sometimes', 'string', 'max:500'],
            'about_title' => ['sometimes', 'string', 'max:255'],
            'about_content' => ['sometimes', 'string', 'max:5000'],
            'years_of_experience' => ['sometimes', 'string', 'max:20'],
            'projects_completed' => ['sometimes', 'string', 'max:20'],
            'area_delivered' => ['sometimes', 'string', 'max:20'],
            'ongoing_projects' => ['sometimes', 'string', 'max:20'],
            'facebook_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'instagram_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'linkedin_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'seo_title' => ['sometimes', 'string', 'max:255'],
            'seo_description' => ['sometimes', 'string', 'max:500'],
        ];
    }
}
