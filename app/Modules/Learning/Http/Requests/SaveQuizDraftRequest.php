<?php

namespace App\Modules\Learning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Core\Traits\HandleApi;

/**
 * Class SaveQuizDraftRequest
 *
 * Xử lý xác thực cho yêu cầu lưu nháp bài quiz.
 *
 * @package App\Modules\Learning\Http\Requests
 */
class SaveQuizDraftRequest extends FormRequest
{
    use HandleApi;

    /**
     * Xác định xem người dùng có quyền thực hiện request này hay không.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Quy tắc validation cho các tham số đầu vào.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'attempt_id' => [
                'required',
                'uuid',
            ],
            'remaining_seconds' => [
                'required',
                'integer',
                'min:0',
            ],
            'answers' => [
                'required',
                'array',
            ],
            'answers.*.quiz_id' => [
                'required',
                'uuid',
            ],
            'answers.*.selected_option' => [
                'nullable',
                'integer',
            ],
            'answers.*.essay_answer' => [
                'nullable',
                'string',
            ],
        ];
    }

    /**
     * Các thông báo lỗi tùy chỉnh bằng Tiếng Việt.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'attempt_id.required' => 'Mã lượt làm bài (attempt_id) không được để trống.',
            'attempt_id.uuid' => 'Mã lượt làm bài phải là định dạng UUID hợp lệ.',
            'remaining_seconds.required' => 'Thời gian còn lại không được để trống.',
            'remaining_seconds.integer' => 'Thời gian còn lại phải là số nguyên.',
            'remaining_seconds.min' => 'Thời gian còn lại không hợp lệ.',
            'answers.required' => 'Vui lòng cung cấp danh sách câu trả lời.',
            'answers.array' => 'Danh sách câu trả lời phải là định dạng mảng.',
            'answers.*.quiz_id.required' => 'Mã câu hỏi không được để trống.',
            'answers.*.quiz_id.uuid' => 'Mã câu hỏi phải là định dạng UUID hợp lệ.',
            'answers.*.selected_option.integer' => 'Phương án trả lời phải là số nguyên.',
            'answers.*.essay_answer.string' => 'Câu trả lời tự luận phải là chuỗi văn bản.',
        ];
    }
}
