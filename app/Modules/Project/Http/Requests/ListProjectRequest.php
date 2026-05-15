<?php

namespace App\Modules\Project\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;

class ListProjectRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => 'sometimes|nullable|string|max:255',
            'status' => 'sometimes|nullable|string|max:50',
            'type' => 'sometimes|nullable|string|max:50',
            'location' => 'sometimes|nullable|string|max:255',
            'min_price' => 'sometimes|nullable|numeric|min:0',
            'max_price' => 'sometimes|nullable|numeric|min:0',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
        ];
    }
}
