<?php

namespace App\Modules\Recruitment\DTO;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRecruitmentPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'image' => 'nullable|string|max:255',
            'branch_name' => 'nullable|string|max:255',
            'job_position' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'short_description' => 'nullable|string',
            'content' => 'nullable|string',
            'job_description' => 'nullable|string',
            'candidate_requirements' => 'nullable|string',
            'benefits' => 'nullable|string',
            'status' => 'nullable|integer|in:1,2',
        ];
    }

    public function messages(): array
    {
        return [
            'title.max' => 'Tiêu đề không được vượt quá 255 ký tự.',
            'status.in' => 'Trạng thái không hợp lệ.',
        ];
    }
}
