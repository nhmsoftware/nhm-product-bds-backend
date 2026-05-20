<?php

namespace App\Modules\Learning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Core\Traits\HandleApi;

/**
 * Class ViewCoursesRequest
 *
 * Xử lý xác thực cho yêu cầu tải danh sách khóa học bắt buộc.
 *
 * @package App\Modules\Learning\Http\Requests
 */
class ViewCoursesRequest extends FormRequest
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
     * Quy tắc validation cho request này.
     *
     * @return array
     */
    public function rules(): array
    {
        return [];
    }
}
