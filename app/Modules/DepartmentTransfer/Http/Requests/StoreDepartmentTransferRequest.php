<?php

namespace App\Modules\DepartmentTransfer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Core\Traits\HandleApi;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreDepartmentTransferRequest',
    title: 'Store Department Transfer Request',
    description: 'Yêu cầu chuyển phòng ban',
    required: ['target_department', 'reason', 'desired_transfer_date'],
    properties: [
        new OA\Property(property: 'target_department', type: 'string', example: 'Phòng Kinh doanh', description: 'Phòng ban muốn chuyển đến'),
        new OA\Property(property: 'reason', type: 'string', example: 'Muốn thử thách ở lĩnh vực mới', description: 'Lý do chuyển phòng ban'),
        new OA\Property(property: 'desired_transfer_date', type: 'string', format: 'date', example: '2026-06-01', description: 'Ngày mong muốn chuyển'),
    ]
)]
class StoreDepartmentTransferRequest extends FormRequest
{
    use HandleApi;
    /**
     * Xác định xem người dùng có quyền thực hiện request này hay không.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Chặn user bị khóa tài khoản hoặc không đăng nhập
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'target_department' => 'required|string|max:255',
            'reason' => 'required|string|max:1000',
            'desired_transfer_date' => 'required|date|after_or_equal:today',
        ];
    }

    /**
     * Custom message cho validation.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'target_department.required' => 'Vui lòng nhập đầy đủ thông tin.',
            'target_department.string' => 'Phòng ban mục tiêu phải là chuỗi.',
            'target_department.max' => 'Phòng ban mục tiêu không được vượt quá 255 ký tự.',
            'reason.required' => 'Vui lòng nhập đầy đủ thông tin.',
            'reason.string' => 'Lý do phải là chuỗi.',
            'reason.max' => 'Lý do không được vượt quá 1000 ký tự.',
            'desired_transfer_date.required' => 'Vui lòng nhập đầy đủ thông tin.',
            'desired_transfer_date.date' => 'Ngày chuyển phòng ban không hợp lệ.',
            'desired_transfer_date.after_or_equal' => 'Ngày chuyển phòng ban không hợp lệ.',
        ];
    }
}
