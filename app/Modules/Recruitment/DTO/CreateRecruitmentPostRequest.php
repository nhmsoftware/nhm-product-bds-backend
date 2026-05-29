<?php

namespace App\Modules\Recruitment\DTO;

use Illuminate\Foundation\Http\FormRequest;
use App\Modules\Recruitment\Models\Enums\RecruitmentPostStatus;

class CreateRecruitmentPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'image' => 'nullable|string|max:255',
            'branch_name' => 'required|string|max:255',
            'job_position' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'short_description' => 'nullable|string',
            'content' => 'nullable|string',
            'job_description' => 'nullable|string',
            'candidate_requirements' => 'nullable|string',
            'benefits' => 'nullable|string',
            'status' => 'required|integer|in:1,2',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Vui lòng nhập tiêu đề bài tuyển dụng.',
            'title.max' => 'Tiêu đề không được vượt quá 255 ký tự.',
            'branch_name.required' => 'Vui lòng nhập tên chi nhánh.',
            'job_position.required' => 'Vui lòng nhập vị trí tuyển dụng.',
            'department.required' => 'Vui lòng nhập phòng ban.',
            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.in' => 'Trạng thái không hợp lệ.',
        ];
    }
}
