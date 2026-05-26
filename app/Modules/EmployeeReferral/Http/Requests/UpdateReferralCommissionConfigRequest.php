<?php

declare(strict_types=1);

namespace App\Modules\EmployeeReferral\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UpdateReferralCommissionConfigRequest',
    title: 'Update Referral Commission Config Request',
    description: 'Yêu cầu cập nhật cấu hình hoa hồng referral',
    required: ['configs'],
    properties: [
        new OA\Property(
            property: 'configs',
            type: 'array',
            items: new OA\Items(
                required: ['referral_type', 'amount'],
                properties: [
                    new OA\Property(property: 'referral_type', type: 'integer', example: 1, description: '1: Tuyển dụng, 2: Khách hàng'),
                    new OA\Property(property: 'amount', type: 'string', example: '500000', description: 'Số tiền hoa hồng mới'),
                ]
            )
        )
    ]
)]
class UpdateReferralCommissionConfigRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Phân quyền sẽ được xử lý ở Service layer
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'configs' => 'required|array|min:1',
            'configs.*.referral_type' => 'required|integer|in:1,2',
            'configs.*.amount' => 'required|numeric|min:0',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'configs' => 'danh sách cấu hình',
            'configs.*.referral_type' => 'loại referral',
            'configs.*.amount' => 'số tiền hoa hồng',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'configs.*.amount.required' => 'Vui lòng nhập giá trị hoa hồng.',
            'configs.*.amount.numeric' => 'Giá trị hoa hồng không hợp lệ.',
            'configs.*.amount.min' => 'Giá trị hoa hồng không hợp lệ.',
            'required' => 'Trường :attribute không được bỏ trống.',
            'array' => 'Trường :attribute phải là một mảng.',
            'min' => 'Trường :attribute phải có giá trị tối thiểu là :min.',
            'integer' => 'Trường :attribute phải là số nguyên.',
            'in' => 'Trường :attribute không hợp lệ.',
            'numeric' => 'Trường :attribute phải là số.',
        ];
    }
}
