<?php

namespace App\Modules\Project\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;

class BulkCreateProjectRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Project validation
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

            // Area validation
            'area' => 'nullable|array',
            'area.name' => 'required_with:area|string|max:255',
            'area.sales_board_image' => 'nullable|string|url',
            'area.sales_board_iframe' => 'nullable|string|url',
            'area.planning_check_url' => 'nullable|string|url',
            'area.sales_board_images' => 'nullable|array',
            'area.sales_board_images.*' => 'string|url',
            'area.area_size' => 'nullable|numeric|min:0',
            'area.direction' => 'nullable|string|max:100',
            'area.status' => 'nullable|integer',
            'area.total_lots' => 'nullable|integer|min:0',

            // Lots validation
            'lots' => 'nullable|array',
            'lots.*.code' => 'required_with:lots|string|max:100',
            'lots.*.status' => 'required_with:lots|integer',
            'lots.*.area_size' => 'nullable|numeric|min:0',
            'lots.*.direction' => 'nullable|string|max:100',
            'lots.*.price' => 'nullable|integer|min:0',
            'lots.*.unit_price' => 'nullable|integer|min:0',
            'lots.*.frontage' => 'nullable|numeric|min:0',
            'lots.*.legal' => 'nullable|string|max:255',
            'lots.*.description' => 'nullable|string',
            'lots.*.coordinate_x' => 'nullable|integer',
            'lots.*.coordinate_y' => 'nullable|integer',
            'lots.*.width' => 'nullable|integer',
            'lots.*.height' => 'nullable|integer',
            'lots.*.images' => 'nullable|array',
            'lots.*.images.*' => 'string|url',
            'lots.*.planning_id' => 'nullable|string|uuid',
        ];
    }
}
