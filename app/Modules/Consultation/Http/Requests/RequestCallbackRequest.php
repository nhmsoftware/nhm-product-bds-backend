<?php

namespace App\Modules\Consultation\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;

class RequestCallbackRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => 'required|string|max:255',
            'phone' => [
                'required',
                'string',
                'regex:/^(0|\+84)(3|5|7|8|9)[0-9]{8}$/',
            ],
            'preferred_callback_time' => 'required|string|max:255',
            'email' => 'nullable|email|max:100',
            'project_id' => 'nullable|uuid|exists:projects,id',
            'project_name' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Vui lòng nhập họ tên.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.regex' => 'Số điện thoại không hợp lệ.',
            'preferred_callback_time.required' => 'Vui lòng nhập thời gian mong muốn.',
            'email.email' => 'Email không hợp lệ.',
            'project_id.exists' => 'Dự án quan tâm không tồn tại trên hệ thống.',
        ];
    }
}
