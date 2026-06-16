<?php

namespace App\Modules\Consultation\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;

class SubmitConsultationRequest extends FormRequest
{
    use HandleApi;

    /**
     * Xác định xem người dùng có quyền thực hiện yêu cầu này không.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Định nghĩa các quy tắc xác thực cho yêu cầu.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'full_name' => 'required|string|max:255',
            'phone' => [
                'required',
                'string',
                'regex:/^(0|\+84)(3|5|7|8|9)[0-9]{8}$/',
            ],
            'email' => 'nullable|email|max:100',
            'project_id' => 'nullable|uuid|exists:areas,id',
            'project_name' => 'nullable|string|max:255',
            'content' => 'nullable|string',
        ];
    }

    /**
     * Định nghĩa thông điệp lỗi tùy chỉnh cho các quy tắc xác thực.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'Vui lòng nhập họ tên.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.regex' => 'Số điện thoại không hợp lệ.',
            'email.email' => 'Email không hợp lệ.',
            'project_id.exists' => 'Khu đất quan tâm không tồn tại trên hệ thống.',
        ];
    }
}
