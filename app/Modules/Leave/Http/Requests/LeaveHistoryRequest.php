<?php

namespace App\Modules\Leave\Http\Requests;

use App\Core\Requests\ListRequest;

use App\Modules\Leave\Models\Enums\RequestStatus;

/**
 * Request xử lý xác thực và chuẩn hóa các bộ lọc đầu vào của lịch sử nghỉ phép.
 * Kế thừa lớp ListRequest của Core để tận dụng cơ chế lọc, phân trang tự động.
 */
final class LeaveHistoryRequest extends ListRequest
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
        
        $validLeaveTypes = [];
        foreach (\App\Modules\Leave\Enums\LeaveType::cases() as $case) {
            $validLeaveTypes[] = $case->value;
            $validLeaveTypes[] = (string) $case->value;
            $validLeaveTypes[] = $case->serialize();
        }

        $validStatuses = [];
        foreach (RequestStatus::cases() as $case) {
            $validStatuses[] = $case->value;
            $validStatuses[] = (string) $case->value;
            $validStatuses[] = $case->serialize();
        }
        
        $rules['filters.leave_type'] = ['sometimes', 'nullable', 'in:' . implode(',', $validLeaveTypes)];
        $rules['filters.status'] = ['sometimes', 'nullable', 'in:' . implode(',', $validStatuses)];
        
        return $rules;
    }
}
