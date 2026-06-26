<?php

namespace App\Modules\Auth\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Auth\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface AuthRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Tìm người dùng theo email.
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email);

    /**
     * Lấy danh sách nhân viên đang hoạt động trong phòng ban.
     *
     * @param string $departmentName
     * @return Collection
     */
    public function getActiveEmployeesByDepartment(string $departmentName): Collection;

    public function getScopedActiveEmployees(User $user, bool $withEmployeeProfile = false): Collection;

    public function getFilteredScopedActiveEmployees(
        User $user,
        ?string $search,
        ?string $jobPosition,
        bool $withEmployeeProfile = false
    ): Collection;

    public function hasActiveEmployeesWithDepartment(): bool;

    public function getActiveEmployeesWithDepartment(?string $branchId = null): Collection;

    public function getActiveDepartmentNames(?string $branchId = null): array;

    /**
     * Lấy danh sách người dùng đang hoạt động cần nhận thông báo khi có bài viết nội bộ mới.
     *
     * @param string|null $department Phòng ban của bài viết
     * @param string|null $branchId ID chi nhánh của bài viết
     * @param string $authorId UUID của người tạo bài viết
     * @return Collection Danh sách người dùng nhận thông báo
     */
    public function getActiveUsersForInternalPost(?string $department, ?string $branchId, string $authorId): Collection;

    /**
     * Tìm người dùng theo số điện thoại.
     *
     * @param string $phone
     * @return User|null
     */
    public function findByPhone(string $phone);

    /**
     * Tìm người dùng theo mã nhân viên (dùng làm mã giới thiệu).
     *
     * @param string $staffCode
     * @return User|null
     */
    public function findByStaffCode(string $staffCode);

    /**
     * Đếm số lượng nhân viên đang hoạt động trong đội ngũ.
     *
     * @param User $user
     * @return int
     */
    public function countActiveTeamMembers(User $user): int;

    /**
     * Lấy danh sách nhân viên đang hoạt động trong đội ngũ.
     *
     * @param User $user
     * @param string|null $search
     * @param string|null $jobPosition
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getActiveTeamMembers(
        User $user,
        ?string $search,
        ?string $jobPosition,
        int $perPage
    ): LengthAwarePaginator;

    /**
     * Lấy dữ liệu báo cáo nhân viên.
     */
    public function getEmployeeReportData(
        User $director,
        ?string $department,
        ?string $employeeId,
        ?string $startDate,
        ?string $endDate
    ): Collection;

    /**
     * Lấy dữ liệu báo cáo phòng ban.
     */
    public function getDepartmentReportData(
        User $director,
        ?string $department,
        ?string $startDate,
        ?string $endDate
    ): Collection;

    /**
     * Đếm số lượng nhân viên theo chi nhánh.
     *
     * @param string|null $branchId
     * @return int
     */
    public function countEmployees(?string $branchId): int;

    /**
     * Đếm số lượng phòng ban theo chi nhánh.
     *
     * @param string|null $branchId
     * @return int
     */
    public function countDepartments(?string $branchId): int;

    /**
     * Đếm số lượng khách hàng theo chi nhánh.
     *
     * @param string|null $branchId
     * @param int|null $month
     * @param int|null $quarter
     * @param int|null $year
     * @return int
     */
    public function countCustomers(?string $branchId, ?int $month, ?int $quarter, ?int $year): int;

    /**
     * Lấy dữ liệu thống kê phòng ban cho dashboard.
     *
     * @param string|null $branchId
     * @param int|null $month
     * @param int|null $quarter
     * @param int|null $year
     * @return Collection
     */
    public function getDepartmentStatsForDashboard(?string $branchId, ?int $month, ?int $quarter, ?int $year): Collection;

    /**
     * Thêm điểm thưởng cho người dùng.
     *
     * @param string $userId
     * @param int $points
     * @return void
     */
    public function addRewardPoints(string $userId, int $points): void;

    /**
     * Tìm các quản lý (Manager, Director, SuperAdmin) đang hoạt động trong cùng chi nhánh.
     *
     * @param string      $branchId     UUID chi nhánh cần tìm
     * @param string      $excludeUserId UUID người dùng cần loại trừ (người tạo đơn)
     * @return Collection Danh sách quản lý phù hợp
     */
    public function findManagersByBranch(string $branchId, string $excludeUserId): Collection;
}
