<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Core\Traits\HandleApi;

class GetTeamMembersRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'job_position' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'search.string' => 'Từ khóa tìm kiếm phải là chuỗi ký tự.',
            'search.max' => 'Từ khóa tìm kiếm không được vượt quá 255 ký tự.',
            'job_position.string' => 'Vị trí công việc phải là chuỗi ký tự.',
            'job_position.max' => 'Vị trí công việc không được vượt quá 255 ký tự.',
            'per_page.integer' => 'Số lượng trên trang phải là số nguyên.',
            'per_page.min' => 'Số lượng trên trang phải lớn hơn 0.',
            'per_page.max' => 'Số lượng trên trang không được vượt quá 100.',
        ];
    }
}
