<?php

namespace App\Modules\Project\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;

class AssignProjectPermissionRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assignable_id' => 'required|uuid',
            'assignable_type' => 'required|string|in:user,department',
            'permissions' => 'required|array',
            'permissions.*' => 'required|string|in:view_project,view_area,view_lot,lock_lot,deposit_lot',
        ];
    }
}
