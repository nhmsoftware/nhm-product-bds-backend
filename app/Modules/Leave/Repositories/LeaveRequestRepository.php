<?php

namespace App\Modules\Leave\Repositories;

use App\Core\DTOs\FilterDTO;
use App\Core\Repository\BaseRepository;
use App\Modules\Leave\Interfaces\LeaveRequestRepositoryInterface;
use App\Modules\Leave\Models\LeaveRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Modules\Leave\Models\Enums\RequestStatus;
use App\Modules\Auth\Models\Enums\UserRole;

/**
 * Repository xử lý các tương tác trực tiếp với cơ sở dữ liệu cho Model LeaveRequest.
 */
final class LeaveRequestRepository extends BaseRepository implements LeaveRequestRepositoryInterface
{
    /**
     * Xác định class Model liên kết với Repository này.
     *
     * @return string
     */
    public function getModel(): string
    {
        return LeaveRequest::class;
    }

    /**
     * Kiểm tra xem nhân viên đã có yêu cầu nghỉ phép nào chồng lấp/trùng thời gian với khoảng thời gian yêu cầu hay chưa.
     * Chỉ kiểm tra với các đơn chưa bị từ chối (trạng thái khác 'rejected').
     *
     * @param string $userId
     * @param string $startDate
     * @param string $endDate
     * @return bool
     */
    public function hasOverlappingLeave(string $userId, string $startDate, string $endDate): bool
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('status', '!=', RequestStatus::REJECTED->value)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->where('start_date', '<=', $endDate)
                      ->where('end_date', '>=', $startDate);
            })
            ->exists();
    }

    /**
     * Lấy danh sách lịch sử yêu cầu nghỉ phép của nhân viên có phân trang và lọc.
     *
     * @param string $userId
     * @param FilterDTO $filter
     * @return LengthAwarePaginator
     */
    public function getLeaveHistory(string $userId, FilterDTO $filter): LengthAwarePaginator
    {
        $query = $this->query()
            ->where('user_id', $userId);

        $filters = $filter->getFilters();

        // Lọc theo loại nghỉ phép
        if (!empty($filters['leave_type'])) {
            try {
                $enum = \App\Modules\Leave\Enums\LeaveType::deserialize($filters['leave_type']);
                $query->where('leave_type', $enum->value);
            } catch (\InvalidArgumentException $e) {
                $query->whereRaw('1 = 0');
            }
        }

        // Lọc theo trạng thái xử lý
        if (!empty($filters['status'])) {
            try {
                $enum = RequestStatus::deserialize($filters['status']);
                $query->where('status', $enum->value);
            } catch (\InvalidArgumentException $e) {
                $query->whereRaw('1 = 0');
            }
        }

        // Sắp xếp
        $sortBy = $filter->getSortBy() ?? 'created_at';
        $direction = $filter->getDirection();

        return $query->orderBy($sortBy, $direction)
            ->paginate($filter->getPerPage(), ['*'], 'page', $filter->getPage());
    }

    /**
     * Lấy danh sách yêu cầu nghỉ phép của phòng ban (tất cả các nhân viên có vai trò agent) có phân trang và lọc.
     *
     * @param FilterDTO $filter
     * @return LengthAwarePaginator
     */
    public function getDepartmentLeaveRequests(FilterDTO $filter): LengthAwarePaginator
    {
        $query = $this->query()
            ->whereHas('user', function ($q) {
                $q->where('role', UserRole::EMPLOYEE->value);
            })
            ->with(['user.employeeProfile']);

        $filters = $filter->getFilters();

        // Lọc theo loại nghỉ phép
        if (!empty($filters['leave_type'])) {
            try {
                $enum = \App\Modules\Leave\Enums\LeaveType::deserialize($filters['leave_type']);
                $query->where('leave_type', $enum->value);
            } catch (\InvalidArgumentException $e) {
                $query->whereRaw('1 = 0');
            }
        }

        // Lọc theo trạng thái xử lý
        if (!empty($filters['status'])) {
            try {
                $enum = RequestStatus::deserialize($filters['status']);
                $query->where('status', $enum->value);
            } catch (\InvalidArgumentException $e) {
                $query->whereRaw('1 = 0');
            }
        }

        // Sắp xếp
        $sortBy = $filter->getSortBy() ?? 'created_at';
        $direction = $filter->getDirection();

        return $query->orderBy($sortBy, $direction)
            ->paginate($filter->getPerPage(), ['*'], 'page', $filter->getPage());
    }
}
