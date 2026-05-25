<?php

namespace App\Modules\Learning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Core\Traits\HandleApi;

class AdminUpdateCourseStatusRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'is_active' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'is_active.required' => 'Trạng thái hoạt động là bắt buộc.',
            'is_active.boolean' => 'Trạng thái hoạt động phải là kiểu boolean.',
        ];
    }
}
