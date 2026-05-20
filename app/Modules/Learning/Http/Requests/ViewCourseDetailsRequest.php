<?php

namespace App\Modules\Learning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Core\Traits\HandleApi;

/**
 * Class ViewCourseDetailsRequest
 *
 * Xử lý xác thực cho yêu cầu tải chi tiết khóa học.
 *
 * @package App\Modules\Learning\Http\Requests
 */
class ViewCourseDetailsRequest extends FormRequest
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

    /**
     * Chuẩn bị dữ liệu để xác thực (nếu cần lấy từ route parameters).
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'id' => $this->route('id'),
        ]);
    }

    /**
     * Các quy tắc validation cho các tham số đã chuẩn bị.
     */
    public function validationData()
    {
        return array_merge($this->all(), [
            'id' => $this->route('id'),
        ]);
    }
}
