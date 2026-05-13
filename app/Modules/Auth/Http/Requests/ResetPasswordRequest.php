<?php

namespace App\Modules\Auth\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'otp'      => ['required', 'string', 'size:6'],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[0-9]/',
                'regex:/[@$!%*#?&]/',
            ],
            'password_confirmation' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'Vui lòng nhập mật khẩu mới',
            'password.min'      => 'Mật khẩu phải có ít nhất 8 ký tự, bao gồm ký tự đặc biệt và số.',
            'password.regex'    => 'Mật khẩu phải có ít nhất 8 ký tự, bao gồm ký tự đặc biệt và số.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
            'password_confirmation.required' => 'Vui lòng xác nhận mật khẩu',
        ];
    }
}
