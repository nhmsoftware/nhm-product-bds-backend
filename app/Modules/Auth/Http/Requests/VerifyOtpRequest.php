<?php

namespace App\Modules\Auth\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
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
        ];
    }

    public function messages(): array
    {
        return [
            'otp.required' => 'Vui lòng nhập mã OTP',
            'otp.size'     => 'Mã OTP phải gồm 6 chữ số.',
        ];
    }
}
