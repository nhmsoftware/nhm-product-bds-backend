<?php

namespace App\Modules\Learning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Core\Traits\HandleApi;

class AdminUpdateCourseRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('courses', 'title')
                    ->ignore($this->route('id'))
                    ->whereNull('deleted_at'),
            ],
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|string|max:500',
            'is_required' => 'nullable|boolean',
            'allowed_roles' => 'nullable|array',
            'allowed_roles.*' => 'integer|in:1,2,3,4',
            'department' => 'nullable|string|max:100',
            'job_position' => 'nullable|string|max:100',
            'order' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'has_certificate' => 'nullable|boolean',
            'lessons' => 'required|array|min:1',
            'lessons.*.id' => 'nullable|uuid',
            'lessons.*.title' => 'required|string|max:255',
            'lessons.*.content' => 'nullable|string',
            'lessons.*.video_url' => 'nullable|url',
            'lessons.*.duration_seconds' => 'required|integer|min:1',
            'lessons.*.order' => 'nullable|integer|min:1',
            'lessons.*.is_active' => 'nullable|boolean',
            'lessons.*.attachments' => 'nullable|array',
            'lessons.*.attachments.*.name' => 'required_with:lessons.*.attachments|string|max:255',
            'lessons.*.attachments.*.url' => 'required_with:lessons.*.attachments|url',
            'lessons.*.quizzes' => 'nullable|array',
            'lessons.*.quizzes.*.id' => 'nullable|uuid',
            'lessons.*.quizzes.*.question' => 'required_with:lessons.*.quizzes|string',
            'lessons.*.quizzes.*.options' => 'required_with:lessons.*.quizzes|array|min:2',
            'lessons.*.quizzes.*.options.*' => 'required_with:lessons.*.quizzes|string',
            'lessons.*.quizzes.*.correct_option' => 'required_with:lessons.*.quizzes|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Thông tin khóa học không hợp lệ.',
            'title.unique' => 'Tên khóa học đã tồn tại',
            'lessons.required' => 'Vui lòng thêm ít nhất một bài học.',
            'lessons.min' => 'Vui lòng thêm ít nhất một bài học.',
            'lessons.*.title.required' => 'Thông tin khóa học không hợp lệ.',
            'lessons.*.duration_seconds.required' => 'Thông tin khóa học không hợp lệ.',
            'lessons.*.duration_seconds.integer' => 'Thông tin khóa học không hợp lệ.',
            'lessons.*.attachments.*.name.required_with' => 'Thông tin khóa học không hợp lệ.',
            'lessons.*.attachments.*.url.required_with' => 'Thông tin khóa học không hợp lệ.',
            'lessons.*.quizzes.*.question.required_with' => 'Thông tin khóa học không hợp lệ.',
            'lessons.*.quizzes.*.options.required_with' => 'Thông tin khóa học không hợp lệ.',
            'lessons.*.quizzes.*.correct_option.required_with' => 'Thông tin khóa học không hợp lệ.',
            'lessons.*.video_url.url' => 'File video không hợp lệ.',
            'lessons.*.attachments.*.url.url' => 'File tài liệu không hợp lệ.',
        ];
    }
}
