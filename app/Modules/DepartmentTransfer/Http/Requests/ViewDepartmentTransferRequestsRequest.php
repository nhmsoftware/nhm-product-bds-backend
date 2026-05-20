<?php

namespace App\Modules\DepartmentTransfer\Http\Requests;

use App\Core\Requests\ListRequest;

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

        $rules['filters.status'] = ['sometimes', 'nullable', 'string', 'in:pending,approved,rejected'];

        return $rules;
    }
}
