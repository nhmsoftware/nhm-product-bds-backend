<?php

namespace App\Modules\Auth\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string'], // Sẽ được xử lý logic xác định email/phone ở Service
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'Vui lòng nhập email hoặc số điện thoại',
            'password.required' => 'Vui lòng nhập mật khẩu',
        ];
    }
}
