<?php

namespace App\Modules\Leave\Http\Requests;

use App\Core\Requests\ListRequest;

/**
 * Request xử lý xác thực và chuẩn hóa các bộ lọc đầu vào để Team Leader xem danh sách nghỉ phép.
 */
final class ViewLeaveRequestsRequest extends ListRequest
{
    protected int $defaultPerPage = 10;
    protected string $defaultSortBy = 'created_at';
    protected string $defaultDirection = 'desc';

    protected array $allowedSorts = ['created_at', 'start_date', 'end_date'];
    protected array $allowedFilters = ['leave_type', 'status'];

    /**
     * Bổ sung các rules kiểm tra tính hợp lệ cụ thể cho bộ lọc nghỉ phép.
     *
     * @return array
     */
    public function rules(): array
    {
        $rules = parent::rules();
        
        $rules['filters.leave_type'] = ['sometimes', 'nullable', 'string', 'in:annual,unpaid,personal,maternity,business,compensatory'];
        $rules['filters.status'] = ['sometimes', 'nullable', 'string', 'in:pending,approved,rejected'];
        
        return $rules;
    }
}
