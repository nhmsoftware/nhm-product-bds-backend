<?php

namespace App\Modules\Learning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Core\Traits\HandleApi;

class AdminCreateCourseQuizRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'passing_score' => 'required|numeric|min:0|max:100',
            'questions' => 'required|array|min:1',
            'questions.*.question' => 'required|string',
            'questions.*.options' => 'required|array|min:2',
            'questions.*.options.*' => 'required|string',
            'questions.*.correct_option' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Vui lòng nhập đầy đủ thông tin bài quiz.',
            'passing_score.required' => 'Vui lòng nhập đầy đủ thông tin bài quiz.',
            'passing_score.numeric' => 'Điểm đạt yêu cầu không hợp lệ.',
            'passing_score.min' => 'Điểm đạt yêu cầu không hợp lệ.',
            'passing_score.max' => 'Điểm đạt yêu cầu không hợp lệ.',
            'questions.required' => 'Vui lòng thêm ít nhất một câu hỏi.',
            'questions.min' => 'Vui lòng thêm ít nhất một câu hỏi.',
            'questions.*.question.required' => 'Vui lòng nhập đầy đủ thông tin bài quiz.',
            'questions.*.options.required' => 'Vui lòng nhập đầy đủ thông tin bài quiz.',
            'questions.*.correct_option.required' => 'Vui lòng nhập đầy đủ thông tin bài quiz.',
        ];
    }
}
