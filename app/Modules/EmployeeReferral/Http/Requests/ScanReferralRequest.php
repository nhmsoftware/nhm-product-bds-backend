<?php

declare(strict_types=1);

namespace App\Modules\EmployeeReferral\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;
use App\Modules\EmployeeReferral\Models\Enums\ReferralType;

class ScanReferralRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $validTypes = [];
        foreach (ReferralType::cases() as $case) {
            $validTypes[] = $case->value;
        }

        return [
            'referral_code' => ['required', 'string', 'max:50'],
            'referral_type' => ['required', 'integer', 'in:' . implode(',', $validTypes)],
            'name'          => ['required', 'string', 'max:255'],
            'phone'         => ['required', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'referral_code.required' => 'Vui lòng cung cấp mã giới thiệu.',
            'referral_type.required' => 'Vui lòng chọn loại QR giới thiệu.',
            'referral_type.in'       => 'Loại QR giới thiệu không hợp lệ.',
            'name.required'          => 'Vui lòng nhập họ tên.',
            'phone.required'         => 'Vui lòng nhập số điện thoại.',
        ];
    }
}
