<?php

namespace App\Modules\Learning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Core\Traits\HandleApi;

/**
 * Class SubmitQuizRequest
 *
 * Xử lý xác thực cho yêu cầu nộp bài quiz.
 *
 * @package App\Modules\Learning\Http\Requests
 */
class SubmitQuizRequest extends FormRequest
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
            'answers' => [
                'required',
                'array',
            ],
            'answers.*.quiz_id' => [
                'required',
                'uuid',
            ],
            'answers.*.selected_option' => [
                'required',
                'integer',
            ],
            'is_timeout' => [
                'nullable',
                'boolean',
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
            'answers.required' => 'Vui lòng cung cấp danh sách câu trả lời.',
            'answers.array' => 'Danh sách câu trả lời phải là định dạng mảng.',
            'answers.*.quiz_id.required' => 'Mã câu hỏi không được để trống.',
            'answers.*.quiz_id.uuid' => 'Mã câu hỏi phải là định dạng UUID hợp lệ.',
            'answers.*.selected_option.required' => 'Vui lòng chọn phương án trả lời.',
            'answers.*.selected_option.integer' => 'Phương án trả lời phải là số nguyên.',
            'is_timeout.boolean' => 'Giá trị is_timeout phải là kiểu boolean.',
        ];
    }
}
