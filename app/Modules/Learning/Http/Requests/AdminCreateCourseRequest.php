<?php

namespace App\Modules\Learning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Core\Traits\HandleApi;

class AdminCreateCourseRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255|unique:courses,title,NULL,id,deleted_at,NULL',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|string|max:500',
            'is_required' => 'nullable|boolean',
            'department' => 'nullable|string|max:100',
            'job_position' => 'nullable|string|max:100',
            'order' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'has_certificate' => 'nullable|boolean',
            'lessons' => 'required|array|min:1',
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
            'lessons.*.quizzes.*.question' => 'required_with:lessons.*.quizzes|string',
            'lessons.*.quizzes.*.options' => 'required_with:lessons.*.quizzes|array|min:2',
            'lessons.*.quizzes.*.options.*' => 'required_with:lessons.*.quizzes|string',
            'lessons.*.quizzes.*.correct_option' => 'required_with:lessons.*.quizzes|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Vui lòng nhập đầy đủ thông tin khóa học.',
            'title.unique' => 'Tên khóa học đã tồn tại',
            'lessons.required' => 'Vui lòng thêm ít nhất một bài học.',
            'lessons.min' => 'Vui lòng thêm ít nhất một bài học.',
            'lessons.*.title.required' => 'Vui lòng nhập đầy đủ thông tin khóa học.',
            'lessons.*.duration_seconds.required' => 'Vui lòng nhập đầy đủ thông tin khóa học.',
            'lessons.*.duration_seconds.integer' => 'Vui lòng nhập đầy đủ thông tin khóa học.',
            'lessons.*.attachments.*.name.required_with' => 'Vui lòng nhập đầy đủ thông tin khóa học.',
            'lessons.*.attachments.*.url.required_with' => 'Vui lòng nhập đầy đủ thông tin khóa học.',
            'lessons.*.quizzes.*.question.required_with' => 'Vui lòng nhập đầy đủ thông tin khóa học.',
            'lessons.*.quizzes.*.options.required_with' => 'Vui lòng nhập đầy đủ thông tin khóa học.',
            'lessons.*.quizzes.*.correct_option.required_with' => 'Vui lòng nhập đầy đủ thông tin khóa học.',
            'lessons.*.video_url.url' => 'File video không hợp lệ.',
            'lessons.*.attachments.*.url.url' => 'File tài liệu không hợp lệ.',
        ];
    }
}
