<?php

namespace App\Modules\Auth\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    use HandleApi;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'new_password'     => [
                'required',
                'string',
                'min:8',
                'regex:/[0-9]/',       // Ít nhất 1 số
                'regex:/[@$!%*#?&]/',  // Ít nhất 1 ký tự đặc biệt
                'different:current_password',
            ],
            'new_password_confirmation' => ['required', 'string', 'same:new_password'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'new_password.required'     => 'Vui lòng nhập mật khẩu mới.',
            'new_password.min'          => 'Mật khẩu phải có ít nhất 8 ký tự, bao gồm ký tự đặc biệt và số.',
            'new_password.regex'        => 'Mật khẩu phải có ít nhất 8 ký tự, bao gồm ký tự đặc biệt và số.',
            'new_password.different'    => 'Mật khẩu mới không được trùng với mật khẩu hiện tại.',
            'new_password_confirmation.required' => 'Vui lòng xác nhận mật khẩu mới.',
            'new_password_confirmation.same'     => 'Mật khẩu xác nhận không khớp.',
        ];
    }
}
