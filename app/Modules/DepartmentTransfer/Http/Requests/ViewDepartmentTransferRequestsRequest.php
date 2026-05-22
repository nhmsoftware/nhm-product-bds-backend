<?php

namespace App\Modules\DepartmentTransfer\Http\Requests;

use App\Core\Requests\ListRequest;

use App\Modules\DepartmentTransfer\Models\Enums\RequestStatus;

/**
 * Request xử lý xác thực và chuẩn hóa các bộ lọc đầu vào để Director/Admin xem danh sách chuyển phòng ban.
 */
final class ViewDepartmentTransferRequestsRequest extends ListRequest
{
    protected int $defaultPerPage = 10;
    protected string $defaultSortBy = 'created_at';
    protected string $defaultDirection = 'desc';

    protected array $allowedSorts = ['created_at', 'desired_transfer_date'];
    protected array $allowedFilters = ['status'];

    /**
     * Bổ sung các rules kiểm tra tính hợp lệ cụ thể cho bộ lọc chuyển phòng ban.
     *
     * @return array
     */
    public function rules(): array
    {
        $rules = parent::rules();

        $validStatuses = [];
        foreach (RequestStatus::cases() as $case) {
            $validStatuses[] = $case->value;
            $validStatuses[] = (string) $case->value;
            $validStatuses[] = $case->serialize();
        }

        $rules['filters.status'] = ['sometimes', 'nullable', 'in:' . implode(',', $validStatuses)];

        return $rules;
    }
}
