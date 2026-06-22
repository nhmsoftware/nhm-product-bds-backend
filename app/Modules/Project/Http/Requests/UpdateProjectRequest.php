<?php

namespace App\Modules\Project\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:50',
            'location' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'status' => 'nullable|integer',
            'type' => 'nullable|string|max:100',
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

            'areas' => 'nullable|array',
            'areas.*.id' => 'nullable|uuid',
            'areas.*.name' => 'required_with:areas|string|max:255',
            'areas.*.code' => 'required_with:areas|string|max:50',
            'areas.*.description' => 'nullable|string',
            'areas.*.x' => 'nullable|integer',
            'areas.*.y' => 'nullable|integer',
            'areas.*.width' => 'nullable|integer',
            'areas.*.height' => 'nullable|integer',

            'areas.*.lots' => 'nullable|array',
            'areas.*.lots.*.id' => 'nullable|uuid',
            'areas.*.lots.*.name' => 'required_with:areas.*.lots|string|max:255',
            'areas.*.lots.*.code' => 'required_with:areas.*.lots|string|max:50',
            'areas.*.lots.*.area_code' => 'nullable|string',
            'areas.*.lots.*.price' => 'nullable|numeric|min:0',
            'areas.*.lots.*.status' => 'nullable|integer',
            'areas.*.lots.*.x' => 'nullable|integer',
            'areas.*.lots.*.y' => 'nullable|integer',
            'areas.*.lots.*.width' => 'nullable|integer',
            'areas.*.lots.*.height' => 'nullable|integer',
            'areas.*.lots.*.frontage' => 'nullable|numeric|min:0',
            'areas.*.lots.*.is_corner' => 'nullable|boolean',
            'areas.*.lots.*.legal' => 'nullable|string',
            'areas.*.lots.*.description' => 'nullable|string',
            'areas.*.lots.*.planning_id' => 'nullable|string|uuid',
        ];
    }
}
