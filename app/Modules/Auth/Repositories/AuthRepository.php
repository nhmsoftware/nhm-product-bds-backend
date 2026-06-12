<?php

namespace App\Modules\Auth\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Modules\Auth\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class AuthRepository extends BaseRepository implements AuthRepositoryInterface
{
    public function getModel(): string
    {
        return User::class;
    }

    /**
     * Tìm người dùng theo email.
     *
     * @param string $email
     * @return \App\Modules\Auth\Models\User|null
     */
    public function findByEmail(string $email)
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Lấy danh sách nhân viên đang hoạt động trong phòng ban.
     *
     * @param string $departmentName
     * @return Collection
     */
    public function getActiveEmployeesByDepartment(string $departmentName): Collection
    {
        return $this->model->where('is_active', true)
            ->where('role', UserRole::EMPLOYEE->value)
            ->where('department', $departmentName)
            ->with('employeeProfile')
            ->get();
    }

    public function getScopedActiveEmployees(User $user, bool $withEmployeeProfile = false): Collection
    {
        $query = $this->applyTeamScope(
            $this->model->where('is_active', true)->where('role', UserRole::EMPLOYEE->value),
            $user
        );

        if ($withEmployeeProfile) {
            $query->with('employeeProfile');
        }

        return $query->get();
    }

    public function getFilteredScopedActiveEmployees(
        User $user,
        ?string $search,
        ?string $jobPosition,
        bool $withEmployeeProfile = false
    ): Collection {
        $query = $this->applyTeamScope(
            $this->model->where('is_active', true)->where('role', UserRole::EMPLOYEE->value),
            $user
        );

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', '%' . $search . '%')
                    ->orWhere('staff_code', 'ilike', '%' . $search . '%');
            });
        }

        if ($jobPosition) {
            $query->where('job_position', $jobPosition);
        }

        if ($withEmployeeProfile) {
            $query->with('employeeProfile');
        }

        return $query->orderBy('name', 'asc')->get();
    }

    public function hasActiveEmployeesWithDepartment(): bool
    {
        return $this->model->where('is_active', true)
            ->where('role', UserRole::EMPLOYEE->value)
            ->whereNotNull('department')
            ->where('department', '<>', '')
            ->exists();
    }

    public function getActiveEmployeesWithDepartment(?string $area = null): Collection
    {
        $query = $this->model->where('is_active', true)
            ->where('role', UserRole::EMPLOYEE->value)
            ->whereNotNull('department')
            ->where('department', '<>', '')
            ->with('employeeProfile');

        if ($area) {
            $query->where('area', $area);
        }

        return $query->get();
    }

    public function getActiveDepartmentNames(): array
    {
        return $this->model
            ->where('is_active', true)
            ->whereIn('role', [
                UserRole::EMPLOYEE->value,
                UserRole::MANAGER->value,
                UserRole::DIRECTOR->value,
                UserRole::CEO->value,
                UserRole::SUPER_ADMIN->value,
            ])
            ->whereNotNull('department')
            ->where('department', '<>', '')
            ->orderBy('department', 'asc')
            ->pluck('department')
            ->map(fn ($department) => trim((string) $department))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Lấy danh sách người dùng đang hoạt động cần nhận thông báo khi có bài viết nội bộ mới.
     *
     * @param string|null $department Phòng ban của bài viết
     * @param string|null $area Khu vực của bài viết
     * @param string $authorId UUID của người tạo bài viết
     * @return Collection Danh sách người dùng nhận thông báo
     */
    public function getActiveUsersForInternalPost(?string $department, ?string $area, string $authorId): Collection
    {
        $query = $this->model->where('is_active', true)
            ->where('id', '!=', $authorId);

        if (!empty($department)) {
            $query->where('department', $department);
        } elseif (!empty($area)) {
            $query->where('area', $area);
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query->get();
    }

    /**
     * Tìm người dùng theo số điện thoại.
     *
     * @param string $phone
     * @return \App\Modules\Auth\Models\User|null
     */
    public function findByPhone(string $phone)
    {
        return $this->model->where('phone', $phone)->first();
    }

    /**
     * Tìm người dùng theo mã nhân viên.
     *
     * @param string $staffCode
     * @return \App\Modules\Auth\Models\User|null
     */
    public function findByStaffCode(string $staffCode)
    {
        return $this->model->where('staff_code', $staffCode)->first();
    }

    public function countActiveTeamMembers(User $user): int
    {
        return $this->applyTeamScope($this->model->where('is_active', true), $user)->count();
    }

    public function getActiveTeamMembers(
        User $user,
        ?string $search,
        ?string $jobPosition,
        int $perPage
    ): \Illuminate\Contracts\Pagination\LengthAwarePaginator {
        $query = $this->applyTeamScope($this->model->where('is_active', true), $user);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', '%' . $search . '%')
                    ->orWhere('staff_code', 'ilike', '%' . $search . '%');
            });
        }

        if ($jobPosition) {
            $query->where('job_position', $jobPosition);
        }

        return $query->orderBy('name', 'asc')->paginate($perPage);
    }

    private function applyTeamScope(\Illuminate\Database\Eloquent\Builder $query, User $user): \Illuminate\Database\Eloquent\Builder
    {
        $query->where('id', '!=', $user->id)
            ->where('role', '<', $user->role->value)
            ->where('role', '!=', UserRole::BUYER->value);

        if ($user->role === UserRole::MANAGER) {
            return $query->where('department', $user->department);
        }

        if ($user->role === UserRole::DIRECTOR) {
            return $query->where('area', $user->area);
        }

        return $query;
    }

    /**
     * Lấy dữ liệu báo cáo nhân viên theo ngày.
     *
     * @param User $director
     * @param string|null $department
     * @param string|null $employeeId
     * @param string|null $startDate
     * @param string|null $endDate
     * @return Collection
     */
    public function getEmployeeReportData(
        User $director,
        ?string $department,
        ?string $employeeId,
        ?string $startDate,
        ?string $endDate
    ): Collection {
        $query = $this->model->where(function ($q) use ($director) {
            $hasCondition = false;
            if (!empty($director->department)) {
                $q->orWhere('department', $director->department);
                $hasCondition = true;
            }
            if (!empty($director->area)) {
                $q->orWhere('area', $director->area);
                $hasCondition = true;
            }
            if (!$hasCondition) {
                $q->whereRaw('1 = 0');
            }
        });

        if (!empty($department)) {
            $query->where('department', $department);
        }

        if (!empty($employeeId)) {
            $query->where('id', $employeeId);
        }

        $query->with('employeeProfile')
            ->withCount([
                'lotDepositRequests as successful_transactions' => function ($q) use ($startDate, $endDate) {
                    $q->where('status', 2); // APPROVED
                    if (!empty($startDate)) $q->whereDate('created_at', '>=', $startDate);
                    if (!empty($endDate)) $q->whereDate('created_at', '<=', $endDate);
                },
                'siteTours as site_tours_count' => function ($q) use ($startDate, $endDate) {
                    if (!empty($startDate)) $q->whereDate('created_at', '>=', $startDate);
                    if (!empty($endDate)) $q->whereDate('created_at', '<=', $endDate);
                },
                'customerMeetings as customer_meetings_count' => function ($q) use ($startDate, $endDate) {
                    if (!empty($startDate)) $q->whereDate('created_at', '>=', $startDate);
                    if (!empty($endDate)) $q->whereDate('created_at', '<=', $endDate);
                },
                'referrals as referrals_count' => function ($q) use ($startDate, $endDate) {
                    $q->where('referral_type', 1)->where('status', 2);
                    if (!empty($startDate)) $q->whereDate('created_at', '>=', $startDate);
                    if (!empty($endDate)) $q->whereDate('created_at', '<=', $endDate);
                },
                'attendances as working_days' => function ($q) use ($startDate, $endDate) {
                    $q->whereIn('status', [1, 2]);
                    if (!empty($startDate)) $q->whereDate('work_date', '>=', $startDate);
                    if (!empty($endDate)) $q->whereDate('work_date', '<=', $endDate);
                },
                'attendances as fixed_schedule_absences' => function ($q) use ($startDate, $endDate) {
                    $q->where('status', 3);
                    if (!empty($startDate)) $q->whereDate('work_date', '>=', $startDate);
                    if (!empty($endDate)) $q->whereDate('work_date', '<=', $endDate);
                }
            ]);

        return $query->get();
    }

    /**
     * Lấy dữ liệu báo cáo phòng ban theo ngày.
     *
     * @param User $director
     * @param string|null $department
     * @param string|null $startDate
     * @param string|null $endDate
     * @return Collection
     */
    public function getDepartmentReportData(
        User $director,
        ?string $department,
        ?string $startDate,
        ?string $endDate
    ): Collection {
        $query = $this->model->whereNotNull('department')
            ->where(function ($q) use ($director) {
                $hasCondition = false;
                if (!empty($director->department)) {
                    $q->orWhere('department', $director->department);
                    $hasCondition = true;
                }
                if (!empty($director->area)) {
                    $q->orWhere('area', $director->area);
                    $hasCondition = true;
                }
                if (!$hasCondition) {
                    $q->whereRaw('1 = 0');
                }
            });

        if (!empty($department)) {
            $query->where('department', $department);
        }

        $query->with('employeeProfile')
            ->withCount([
                'lotDepositRequests as successful_transactions' => function ($q) use ($startDate, $endDate) {
                    $q->where('status', 2);
                    if (!empty($startDate)) $q->whereDate('created_at', '>=', $startDate);
                    if (!empty($endDate)) $q->whereDate('created_at', '<=', $endDate);
                },
                'siteTours as site_tours_count' => function ($q) use ($startDate, $endDate) {
                    if (!empty($startDate)) $q->whereDate('created_at', '>=', $startDate);
                    if (!empty($endDate)) $q->whereDate('created_at', '<=', $endDate);
                },
                'customerMeetings as customer_meetings_count' => function ($q) use ($startDate, $endDate) {
                    if (!empty($startDate)) $q->whereDate('created_at', '>=', $startDate);
                    if (!empty($endDate)) $q->whereDate('created_at', '<=', $endDate);
                },
                'referrals as referrals_count' => function ($q) use ($startDate, $endDate) {
                    $q->where('referral_type', 1)->where('status', 2);
                    if (!empty($startDate)) $q->whereDate('created_at', '>=', $startDate);
                    if (!empty($endDate)) $q->whereDate('created_at', '<=', $endDate);
                },
                'attendances as working_days' => function ($q) use ($startDate, $endDate) {
                    $q->whereIn('status', [1, 2]);
                    if (!empty($startDate)) $q->whereDate('work_date', '>=', $startDate);
                    if (!empty($endDate)) $q->whereDate('work_date', '<=', $endDate);
                },
                'attendances as fixed_schedule_absences' => function ($q) use ($startDate, $endDate) {
                    $q->where('status', 3);
                    if (!empty($startDate)) $q->whereDate('work_date', '>=', $startDate);
                    if (!empty($endDate)) $q->whereDate('work_date', '<=', $endDate);
                }
            ]);

        return $query->get();
    }

    /**
     * Đếm số lượng nhân viên theo khu vực.
     *
     * @param string|null $area
     * @return int
     */
    public function countEmployees(?string $area): int
    {
        $query = $this->model->whereIn('role', [UserRole::EMPLOYEE->value, UserRole::MANAGER->value, UserRole::DIRECTOR->value])
            ->where('is_active', true);
        if (!empty($area)) $query->where('area', $area);
        return $query->count();
    }

    /**
     * Đếm số lượng phòng ban theo khu vực.
     *
     * @param string|null $area
     * @return int
     */
    public function countDepartments(?string $area): int
    {
        $query = $this->model->whereNotNull('department')->where('is_active', true);
        if (!empty($area)) $query->where('area', $area);
        return $query->distinct('department')->count('department');
    }

    /**
     * Đếm số lượng khách hàng theo khu vực.
     *
     * @param string|null $area
     * @param int|null $month
     * @param int|null $quarter
     * @param int|null $year
     * @return int
     */
    public function countCustomers(?string $area, ?int $month, ?int $quarter, ?int $year): int
    {
        $query = $this->model->where('role', UserRole::BUYER->value);
        if (!empty($area)) $query->where('area', $area);

        if ($year) {
            $query->whereYear('created_at', $year);
        }
        if ($month) {
            $query->whereMonth('created_at', $month);
        } elseif ($quarter) {
            $startMonth = ($quarter - 1) * 3 + 1;
            $endMonth = $quarter * 3;
            $query->whereMonth('created_at', '>=', $startMonth)
                  ->whereMonth('created_at', '<=', $endMonth);
        }

        return $query->count();
    }

    /**
     * Đếm tổng số điểm KPI theo khu vực.
     *
     * @param string|null $area
     * @return int
     */
    public function countKpiStars(?string $area): int
    {
        $query = $this->model->whereNotNull('department')->where('is_active', true)
            ->join('employee_profiles', 'users.id', '=', 'employee_profiles.user_id');
        if (!empty($area)) $query->where('users.area', $area);
        return (int) $query->sum('employee_profiles.kpi_stars');
    }

    /**
     * Lấy thống kê phòng ban theo khu vực.
     *
     * @param string|null $area
     * @param int|null $month
     * @param int|null $quarter
     * @param int|null $year
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDepartmentStatsForDashboard(?string $area, ?int $month, ?int $quarter, ?int $year): Collection
    {
        $deptUsersQuery = $this->model->whereNotNull('department')
            ->whereIn('role', [UserRole::EMPLOYEE->value, UserRole::MANAGER->value, UserRole::DIRECTOR->value]);

        if (!empty($area)) {
            $deptUsersQuery->where('area', $area);
        }

        $applyDateFilter = function ($q) use ($month, $quarter, $year) {
            if ($year) {
                $q->whereYear('created_at', $year);
            }
            if ($month) {
                $q->whereMonth('created_at', $month);
            } elseif ($quarter) {
                $startMonth = ($quarter - 1) * 3 + 1;
                $endMonth = $quarter * 3;
                $q->whereMonth('created_at', '>=', $startMonth)
                  ->whereMonth('created_at', '<=', $endMonth);
            }
        };

        $deptUsersQuery->with('employeeProfile')
            ->withCount([
                'lotDepositRequests as successful_transactions' => function ($q) use ($applyDateFilter) {
                    $q->where('status', 2);
                    $applyDateFilter($q);
                }
            ]);
        $deptUsersQuery->with(['lotDepositRequests' => function ($q) use ($applyDateFilter) {
            $q->where('status', 2)->with('lot');
            $applyDateFilter($q);
        }]);

        return $deptUsersQuery->get();
    }

    /**
     * Thêm điểm thưởng và điểm KPI cho người dùng.
     *
     * @param string $userId
     * @param int $points
     * @param int $stars
     * @return void
     */
    public function addRewardPointsAndStars(string $userId, int $points, int $stars): void
    {
        $user = $this->model->with('employeeProfile')->find($userId);
        if ($user && $user->employeeProfile) {
            $user->employeeProfile->reward_points += $points;
            $user->employeeProfile->kpi_stars += $stars;
            $user->employeeProfile->save();
        }
    }
}
