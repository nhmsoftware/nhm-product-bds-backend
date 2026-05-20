<?php

namespace App\Modules\Leave\Interfaces;

use App\Core\DTOs\FilterDTO;
use App\Core\Interfaces\BaseRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface định nghĩa các phương thức nghiệp vụ CSDL cho LeaveRequest.
 * Kế thừa từ BaseRepositoryInterface để đảm bảo đầy đủ các phương thức CRUD cơ bản.
 */
interface LeaveRequestRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Kiểm tra xem nhân viên đã có yêu cầu nghỉ phép nào chồng lấp/trùng thời gian với khoảng thời gian yêu cầu hay chưa.
     * Một khoảng thời gian chồng lấp khi có bất kỳ đơn nghỉ nào ở trạng thái pending hoặc approved thỏa mãn:
     * existing_start_date <= requested_end_date VÀ existing_end_date >= requested_start_date
     *
     * @param string $userId    ID của nhân viên
     * @param string $startDate Ngày bắt đầu nghỉ phép (Y-m-d)
     * @param string $endDate   Ngày kết thúc nghỉ phép (Y-m-d)
     * @return bool True nếu bị trùng lặp/chồng lấp thời gian nghỉ, ngược lại là False
     */
    public function hasOverlappingLeave(string $userId, string $startDate, string $endDate): bool;

    /**
     * Lấy danh sách lịch sử yêu cầu nghỉ phép của nhân viên có phân trang và lọc.
     *
     * @param string $userId
     * @param FilterDTO $filter
     * @return LengthAwarePaginator
     */
    public function getLeaveHistory(string $userId, FilterDTO $filter): LengthAwarePaginator;

    /**
     * Lấy danh sách yêu cầu nghỉ phép của phòng ban (tất cả các nhân viên có vai trò agent) có phân trang và lọc.
     *
     * @param FilterDTO $filter
     * @return LengthAwarePaginator
     */
    public function getDepartmentLeaveRequests(FilterDTO $filter): LengthAwarePaginator;
}
