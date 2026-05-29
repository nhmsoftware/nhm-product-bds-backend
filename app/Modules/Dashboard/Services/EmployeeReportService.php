<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use App\Modules\Auth\Models\User;
use App\Modules\Dashboard\DTO\ViewEmployeeReportDTO;
use App\Modules\Dashboard\Interfaces\EmployeeReportServiceInterface;

final class EmployeeReportService extends BaseService implements EmployeeReportServiceInterface
{
    public function __construct(
        private readonly AuthRepositoryInterface $authRepository
    ) {
    }

    /**
     * Lấy báo cáo nhân viên (UC-109)
     * 
     * @param ViewEmployeeReportDTO $dto
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function getEmployeeReports(ViewEmployeeReportDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $director = $this->authRepository->findById($dto->userId);
            $this->validate($director !== null, 'Người dùng không tồn tại.', 404);

            $users = $this->authRepository->getEmployeeReportData($director, $dto->department, $dto->employeeId, $dto->startDate, $dto->endDate);

            $reports = $users->map(function ($user) {
                return [
                    'id' => (string) $user->id,
                    'full_name' => $user->name,
                    'department' => $user->department,
                    'job_position' => $user->job_position,
                    'total_kpi' => $user->employeeProfile ? $user->employeeProfile->kpi_stars : 0,
                    'successful_transactions' => $user->successful_transactions ?? 0,
                    'site_tours' => $user->site_tours_count ?? 0,
                    'customer_meetings' => $user->customer_meetings_count ?? 0,
                    'referrals' => $user->referrals_count ?? 0,
                    'working_days' => $user->working_days ?? 0,
                    'fixed_schedule_absences' => $user->fixed_schedule_absences ?? 0,
                ];
            });

            if ($reports->isEmpty()) {
                return $this->success([], 'Không tìm thấy dữ liệu phù hợp.');
            }

            return $this->success($reports->toArray(), 'Tải báo cáo nhân viên thành công.');
        }, useTransaction: false);
    }

    /**
     * Lấy báo cáo phòng ban (UC-110)
     * 
     * @param \App\Modules\Dashboard\DTO\ViewDepartmentReportDTO $dto
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function getDepartmentReports(\App\Modules\Dashboard\DTO\ViewDepartmentReportDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $director = $this->authRepository->findById($dto->userId);
            $this->validate($director !== null, 'Người dùng không tồn tại.', 404);

            $users = $this->authRepository->getDepartmentReportData($director, $dto->department, $dto->startDate, $dto->endDate);
            
            $departments = $users->groupBy('department')->map(function ($usersInDept, $deptName) {
                return [
                    'department_name' => $deptName,
                    'total_employees' => $usersInDept->count(),
                    'total_kpi' => (int) $usersInDept->sum(fn($u) => $u->employeeProfile ? $u->employeeProfile->kpi_stars : 0),
                    'successful_transactions' => (int) $usersInDept->sum('successful_transactions'),
                    'site_tours' => (int) $usersInDept->sum('site_tours_count'),
                    'customer_meetings' => (int) $usersInDept->sum('customer_meetings_count'),
                    'referrals' => (int) $usersInDept->sum('referrals_count'),
                    'working_days' => (int) $usersInDept->sum('working_days'),
                    'fixed_schedule_absences' => (int) $usersInDept->sum('fixed_schedule_absences'),
                ];
            })->values();

            if ($departments->isEmpty()) {
                return $this->success([], 'Không tìm thấy dữ liệu phù hợp.');
            }

            return $this->success($departments->toArray(), 'Tải báo cáo phòng ban thành công.');
        }, useTransaction: false);
    }
}
