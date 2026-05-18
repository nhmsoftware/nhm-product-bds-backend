<?php

namespace App\Modules\Auth\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeProfileRequest extends FormRequest
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
        $userId = auth('api')->id();

        return [
            'name'                => ['required', 'string', 'max:255'],
            'phone'               => [
                'required',
                'string',
                'regex:/^(03|05|07|08|09)\d{8}$/',
                'unique:users,phone,' . $userId . ',id',
            ],
            'email'               => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email,' . $userId . ',id',
            ],
            'avatar'              => ['nullable', 'string', 'max:255'],
            'dob'                 => ['nullable', 'date', 'before:today'],
            'address'             => ['nullable', 'string', 'max:255'],
            'bank_account_name'   => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'regex:/^\d{6,20}$/'],
            'bank_name'           => ['nullable', 'string', 'max:255'],
            'education'           => ['nullable', 'string'],
            'major'               => ['nullable', 'string', 'max:255'],
            'experience'          => ['nullable', 'string'],
            'employee_title'      => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required'              => 'Vui lòng nhập họ và tên.',
            'phone.required'             => 'Vui lòng nhập số điện thoại.',
            'phone.regex'                => 'Số điện thoại không hợp lệ.',
            'phone.unique'               => 'Số điện thoại đã được sử dụng.',
            'email.required'             => 'Vui lòng nhập email.',
            'email.email'                => 'Email không hợp lệ.',
            'email.unique'               => 'Email đã được sử dụng.',
            'bank_account_number.regex'  => 'Số tài khoản ngân hàng không hợp lệ.',
        ];
    }
}
