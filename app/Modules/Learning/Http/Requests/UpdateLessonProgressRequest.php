<?php

namespace App\Modules\Learning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Core\Traits\HandleApi;

/**
 * Class UpdateLessonProgressRequest
 *
 * Xử lý xác thực cho yêu cầu cập nhật tiến độ xem video bài học.
 *
 * @package App\Modules\Learning\Http\Requests
 */
class UpdateLessonProgressRequest extends FormRequest
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
            'watch_time_seconds' => [
                'required',
                'integer',
                'min:0',
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
            'watch_time_seconds.required' => 'Vui lòng cung cấp thời lượng đã xem video.',
            'watch_time_seconds.integer' => 'Thời lượng xem phải là số nguyên giây.',
            'watch_time_seconds.min' => 'Thời lượng xem không được nhỏ hơn 0 giây.',
        ];
    }
}
