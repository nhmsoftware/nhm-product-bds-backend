<?php

declare(strict_types=1);

namespace App\Modules\EmployeeReferral\Http\Requests;

use App\Core\Request\BaseRequest;
use Illuminate\Validation\Rule;
use App\Modules\EmployeeReferral\Models\Enums\ReferralType;

final class ReferralCommissionReportRequest extends BaseRequest
{
    /**
     * Xác định quyền truy cập (được kiểm tra ở controller).
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true; // Controller sẽ kiểm tra role GD/Super Admin.
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'filters'                 => ['nullable', 'array'],
            'filters.referrer_id'     => ['nullable', 'string', 'uuid'],
            'filters.referral_type'   => ['nullable', 'integer', Rule::in(array_column(ReferralType::cases(), 'value'))],
            'filters.date_from'       => ['nullable', 'date'],
            'filters.date_to'         => ['nullable', 'date', 'after_or_equal:filters.date_from'],
            'page'                    => ['nullable', 'integer', 'min:1'],
            'per_page'                => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'filters.array'               => 'Bộ lọc phải là một mảng.',
            'filters.referrer_id.uuid'    => 'ID nhân viên không hợp lệ.',
            'filters.referral_type.in'    => 'Loại referral không hợp lệ.',
            'filters.date_from.date'      => 'Từ ngày không đúng định dạng.',
            'filters.date_to.date'        => 'Đến ngày không đúng định dạng.',
            'filters.date_to.after_or_equal' => 'Đến ngày phải lớn hơn hoặc bằng từ ngày.',
            'page.integer'                => 'Trang phải là số nguyên.',
            'page.min'                    => 'Trang phải lớn hơn 0.',
            'per_page.integer'            => 'Số lượng mỗi trang phải là số nguyên.',
            'per_page.min'                => 'Số lượng mỗi trang phải lớn hơn 0.',
            'per_page.max'                => 'Số lượng mỗi trang không được vượt quá 100.',
        ];
    }

    public function getFilterOptions(): array
    {
        return [
            'referrer_id'   => $this->input('filters.referrer_id'),
            'referral_type' => $this->input('filters.referral_type'),
            'date_from'     => $this->input('filters.date_from'),
            'date_to'       => $this->input('filters.date_to'),
            'page'          => (int) $this->input('page', 1),
            'per_page'      => (int) $this->input('per_page', 15),
        ];
    }
}
