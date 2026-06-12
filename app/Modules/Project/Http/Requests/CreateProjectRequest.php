<?php

namespace App\Modules\Project\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;

class CreateProjectRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'location' => 'required|string',
            'price' => 'required|numeric|min:0',
            'status' => 'required|integer',
            'type' => 'required|string|max:100',
            'image' => 'nullable|string|url',
            'banner' => 'nullable|array',
            'banner.*' => 'string|url',
            'is_public' => 'nullable|boolean',
            'description' => 'nullable|string',
            'keywords' => 'nullable|array',
            'amenities' => 'nullable|array',
            'floor_plans' => 'nullable|array',
            'legal_info' => 'nullable|array',
            'brochure' => 'nullable|string|url',
            'contact_info' => 'nullable|array',
            'google_maps_url' => 'nullable|string|url',
            'location_image' => 'nullable|string|url',
            'planning_info' => 'nullable|array',
            'branch' => 'nullable|string',
            'total_lots' => 'nullable|integer|min:0',
            'remaining_lots' => 'nullable|integer|min:0',
            'is_featured' => 'nullable|boolean',
            'is_locked' => 'nullable|boolean',
        ];
    }
}
