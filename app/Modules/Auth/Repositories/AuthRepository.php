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
            ->whereHas('role', fn($q) => $q->where('name', 'employee'))
            ->whereNotNull('job_position_id')
            ->where('department_id', function ($q) use ($departmentName) {
                $q->select('id')->from('departments')->where('name', $departmentName);
            })
            ->with('employeeProfile')
            ->get();
    }

    public function getScopedActiveEmployees(User $user, bool $withEmployeeProfile = false): Collection
    {
        $query = $this->applyTeamScope(
            $this->model->where('is_active', true)
                ->whereHas('role', fn($q) => $q->where('name', 'employee'))
                ->whereNotNull('job_position_id'),
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
            $this->model->where('is_active', true)
                ->whereHas('role', fn($q) => $q->where('name', 'employee'))
                ->whereNotNull('job_position_id'),
            $user
        );

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', '%' . $search . '%')
                    ->orWhere('staff_code', 'ilike', '%' . $search . '%');
            });
        }

        if ($jobPosition) {
            if (is_numeric($jobPosition)) {
                $query->where('job_position_id', $jobPosition);
            } else {
                $query->whereIn('job_position_id', function ($q) use ($jobPosition) {
                    $q->select('id')->from('job_positions')->where('name', $jobPosition)->orWhere('code', $jobPosition);
                });
            }
        }

        if ($withEmployeeProfile) {
            $query->with('employeeProfile');
        }

        return $query->orderBy('name', 'asc')->get();
    }

    public function hasActiveEmployeesWithDepartment(): bool
    {
        return $this->model->where('is_active', true)
            ->whereHas('role', fn($q) => $q->where('name', 'employee'))
            ->whereNotNull('job_position_id')
            ->whereNotNull('department_id')
            ->exists();
    }

    public function getActiveEmployeesWithDepartment(?string $branchId = null): Collection
    {
        $query = $this->model->where('is_active', true)
            ->whereHas('role', fn($q) => $q->where('name', 'employee'))
            ->whereNotNull('job_position_id')
            ->whereNotNull('department_id')
            ->with('employeeProfile');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->get();
    }

    public function getActiveDepartmentNames(?string $branchId = null): array
    {
        // Query thẳng từ bảng departments — không phụ thuộc vào việc
        // có nhân viên active trong phòng ban hay không.
        // Loại bỏ các phòng ban hệ thống (ALL, SYSTEM) và phòng ban khách hàng.
        $hiddenCodes = ['ALL', 'SYSTEM', 'CUSTOMER', 'KHACH_HANG'];
        $hiddenNames = ['tất cả', 'hệ thống', 'khách hàng', 'phòng khách hàng'];

        $query = \App\Modules\Auth\Models\Department::query()
            ->whereNotIn('code', $hiddenCodes)
            ->where(function ($q) use ($hiddenNames) {
                foreach ($hiddenNames as $name) {
                    $q->whereRaw('LOWER(name) != ?', [$name]);
                }
            });

        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            });
        }

        return $query->orderBy('name', 'asc')
            ->pluck('name')
            ->map(fn (string $department) => trim($department))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Lấy danh sách người dùng đang hoạt động cần nhận thông báo khi có bài viết nội bộ mới.
     *
     * @param string|null $department Phòng ban của bài viết
     * @param string|null $branchId ID chi nhánh của bài viết
     * @param string $authorId UUID của người tạo bài viết
     * @return Collection Danh sách người dùng nhận thông báo
     */
    public function getActiveUsersForInternalPost(?string $department, ?string $branchId, string $authorId): Collection
    {
        $query = $this->model->where('is_active', true)
            ->where('id', '!=', $authorId);

        if (!empty($department)) {
            $query->where('department_id', function ($q) use ($department) {
                $q->select('id')->from('departments')->where('name', $department);
            });
        } elseif (!empty($branchId)) {
            $query->where('branch_id', $branchId);
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
        $normalized = $this->normalizeReferralStaffCode($staffCode);
        $user = $this->model->where('staff_code', $normalized)->first();
        if (!$user) {
            $user = $this->model->where('phone', $normalized)->first();
        }
        return $user;
    }

    private function normalizeReferralStaffCode(string $referralCode): string
    {
        $code = trim($referralCode);

        if (str_starts_with(strtoupper($code), 'REC-') || str_starts_with(strtoupper($code), 'CUS-')) {
            return substr($code, 4);
        }

        return $code;
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
            if (is_numeric($jobPosition)) {
                $query->where('job_position_id', $jobPosition);
            } else {
                $query->whereIn('job_position_id', function ($q) use ($jobPosition) {
                    $q->select('id')->from('job_positions')->where('name', $jobPosition)->orWhere('code', $jobPosition);
                });
            }
        }

        return $query->orderBy('name', 'asc')->paginate($perPage);
    }

    private function applyTeamScope(\Illuminate\Database\Eloquent\Builder $query, User $user): \Illuminate\Database\Eloquent\Builder
    {
        $query->where('id', '!=', $user->id)
            ->where('role', '<', $user->role->value)
            ->where('role_id', '!=', \App\Modules\Auth\Models\Role::where('name', 'buyer')->value('id'));

        if ($user->role?->name === 'tp_kd') {
            return $query->where('department_id', $user->department_id);
        }

        if ($user->role?->name === 'gdkd') {
            return $query->where('branch_id', $user->branch_id);
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
            if (!empty($director->department_id)) {
                $q->orWhere('department_id', $director->department_id);
                $hasCondition = true;
            }
            if (!empty($director->branch_id)) {
                $q->orWhere('branch_id', $director->branch_id);
                $hasCondition = true;
            }
            if (!$hasCondition) {
                $q->whereRaw('1 = 0');
            }
        });

        if (!empty($department)) {
            $query->where('department_id', function ($q) use ($department) {
                $q->select('id')->from('departments')->where('name', $department);
            });
        }

        if (!empty($employeeId)) {
            $query->where('id', $employeeId);
        }

        $query->with('employeeProfile')
            ->withCount([
                'lotDepositRequests as successful_transactions' => function ($q) use ($startDate, $endDate) {
                    $q->whereIn('status', [2, 4]); // APPROVED or COMPLETED
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
        $query = $this->model->whereNotNull('department_id')
            ->whereNotIn('department_id', function ($q) {
                $q->select('id')->from('departments')->whereIn('code', ['ALL', 'SYSTEM']);
            })
            ->where(function ($q) use ($director) {
                $hasCondition = false;
                if (!empty($director->department_id)) {
                    $q->orWhere('department_id', $director->department_id);
                    $hasCondition = true;
                }
                if (!empty($director->branch_id)) {
                    $q->orWhere('branch_id', $director->branch_id);
                    $hasCondition = true;
                }
                if (!$hasCondition) {
                    $q->whereRaw('1 = 0');
                }
            });

        if (!empty($department)) {
            $query->where('department_id', function ($q) use ($department) {
                $q->select('id')->from('departments')->where('name', $department);
            });
        }

        $query->with('employeeProfile')
            ->withCount([
                'lotDepositRequests as successful_transactions' => function ($q) use ($startDate, $endDate) {
                    $q->whereIn('status', [2, 4]);
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
     * Đếm số lượng nhân viên theo chi nhánh.
     *
     * @param string|null $branchId
     * @return int
     */
    public function countEmployees(?string $branchId): int
    {
        $query = $this->model->whereHas('role', fn($q) => $q->whereIn('name', ['employee', 'tp_kd', 'gdkd']))
            ->where('is_active', true)
            ->where(fn ($q) => $q->where('role_id', '!=', \App\Modules\Auth\Models\Role::where('name', 'employee')->value('id'))->orWhere(fn ($sub) => $sub->whereNotNull('job_position_id')));
        if (!empty($branchId)) $query->where('branch_id', $branchId);
        return $query->count();
    }

    /**
     * Đếm số lượng phòng ban theo chi nhánh.
     *
     * @param string|null $branchId
     * @return int
     */
    public function countDepartments(?string $branchId): int
    {
        $query = $this->model->whereNotNull('department_id')
            ->whereNotIn('department_id', function ($q) {
                $q->select('id')->from('departments')->whereIn('code', ['ALL', 'SYSTEM']);
            })
            ->where('is_active', true);
        if (!empty($branchId)) $query->where('branch_id', $branchId);
        return $query->distinct('department_id')->count('department_id');
    }

    /**
     * Đếm số lượng khách hàng theo chi nhánh.
     *
     * @param string|null $branchId
     * @param int|null $month
     * @param int|null $quarter
     * @param int|null $year
     * @return int
     */
    public function countCustomers(?string $branchId, ?int $month, ?int $quarter, ?int $year): int
    {
        $query = $this->model->whereHas('role', fn($q) => $q->where('name', 'buyer'));
        if (!empty($branchId)) $query->where('branch_id', $branchId);

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
     * Đếm tổng số điểm KPI theo chi nhánh.
     *
     * @param string|null $branchId
     * @return int
     */
    public function getDepartmentStatsForDashboard(?string $branchId, ?int $month, ?int $quarter, ?int $year): Collection
    {
        $deptUsersQuery = $this->model->whereNotNull('department_id')
            ->whereNotIn('department_id', function ($q) {
                $q->select('id')->from('departments')->whereIn('code', ['ALL', 'SYSTEM']);
            })
            ->whereHas('role', fn($q) => $q->whereIn('name', ['employee', 'tp_kd', 'gdkd']));

        if (!empty($branchId)) {
            $deptUsersQuery->where('branch_id', $branchId);
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
                    $q->whereIn('status', [2, 4]);
                    $applyDateFilter($q);
                }
            ]);
        $deptUsersQuery->with(['lotDepositRequests' => function ($q) use ($applyDateFilter) {
            $q->whereIn('status', [2, 4])->with('lot');
            $applyDateFilter($q);
        }]);

        return $deptUsersQuery->get();
    }

    /**
     * Thêm điểm thưởng cho người dùng.
     *
     * @param string $userId
     * @param int $points
     * @return void
     */
    public function addRewardPoints(string $userId, int $points): void
    {
        $user = $this->model->with('employeeProfile')->find($userId);
        if ($user && $user->employeeProfile) {
            $user->employeeProfile->reward_points += $points;
            $user->employeeProfile->save();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findManagersByBranch(string $branchId, string $excludeUserId): Collection
    {
        return $this->model->query()
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->where('id', '!=', $excludeUserId)
            ->whereHas('role', fn($q) => $q->whereIn('name', ['tp_kd', 'gdkd', 'super_admin']))
            ->get();
    }
}
