<?php

namespace App\Modules\Project\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;

class ListAdminProjectRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1',
            'keyword' => 'nullable|string',
            'filters' => 'nullable|array',
            'sort_by' => 'nullable|string|in:created_at,id,name,total_lots,remaining_lots',
            'direction' => 'nullable|string|in:asc,desc',
        ];
    }
}
