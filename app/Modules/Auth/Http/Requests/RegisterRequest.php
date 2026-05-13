<?php

namespace App\Modules\Auth\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone'    => ['required', 'string', 'max:20', 'unique:users,phone', 'regex:/^([0-9\s\-\+\(\)]*)$/'],
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/[0-9]/',      // ít nhất 1 số
                'regex:/[@$!%*#?&]/', // ít nhất 1 ký tự đặc biệt
            ],
            'referral_code' => ['nullable', 'string', 'max:50'],
            'agree_terms' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.regex' => 'Mật khẩu phải có ít nhất 8 ký tự, bao gồm ký tự đặc biệt và số.',
            'agree_terms.accepted' => 'Vui lòng đồng ý Điều khoản dịch vụ và Chính sách bảo mật.',
        ];
    }
}
