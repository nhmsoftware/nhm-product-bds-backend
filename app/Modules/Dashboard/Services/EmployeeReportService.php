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

            $settings = \App\Modules\Area\Models\InventorySetting::pluck('value', 'key');
            $successfulTransactionPoints = (float) data_get($settings->get('kpi_points_successful_transaction'), 'points', 10);
            $siteTourPoints = (float) data_get($settings->get('kpi_points_site_tour'), 'points', 1);
            $customerMeetingPoints = (float) data_get($settings->get('kpi_points_customer_meeting'), 'points', 0.5);
            $successfulReferralPoints = (float) data_get($settings->get('kpi_points_successful_referral'), 'points', 1);
            $workDayPoints = (float) data_get($settings->get('kpi_points_work_day_rate'), 'points', 1);
            $workDaysStep = (int) data_get($settings->get('kpi_points_work_day_rate'), 'days', 5);
            $absencePenalty = (float) data_get($settings->get('kpi_points_absence_penalty'), 'points', 0.5);

            $reports = $users->map(function ($user) use ($successfulTransactionPoints, $siteTourPoints, $customerMeetingPoints, $successfulReferralPoints, $workDayPoints, $workDaysStep, $absencePenalty) {
                $userTransactions = $user->successful_transactions ?? 0;
                $userTours = $user->site_tours_count ?? 0;
                $userMeetings = $user->customer_meetings_count ?? 0;
                $userReferrals = $user->referrals_count ?? 0;
                $userWorkDays = $user->working_days ?? 0;
                $userAbsences = $user->fixed_schedule_absences ?? 0;

                $kpiPoints = ($userTransactions * $successfulTransactionPoints)
                    + ($userTours * $siteTourPoints)
                    + ($userMeetings * $customerMeetingPoints)
                    + ($userReferrals * $successfulReferralPoints)
                    + ($workDaysStep > 0 ? floor($userWorkDays / $workDaysStep) * $workDayPoints : 0)
                    - ($userAbsences * abs($absencePenalty));

                return [
                    'id' => (string) $user->id,
                    'full_name' => $user->name,
                    'department' => $user->department,
                    'job_position' => $user->job_position,
                    'total_kpi' => (float) $kpiPoints,
                    'successful_transactions' => $userTransactions,
                    'site_tours' => $userTours,
                    'customer_meetings' => $userMeetings,
                    'referrals' => $userReferrals,
                    'working_days' => $userWorkDays,
                    'fixed_schedule_absences' => $userAbsences,
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
            
            $settings = \App\Modules\Area\Models\InventorySetting::pluck('value', 'key');
            $successfulTransactionPoints = (float) data_get($settings->get('kpi_points_successful_transaction'), 'points', 10);
            $siteTourPoints = (float) data_get($settings->get('kpi_points_site_tour'), 'points', 1);
            $customerMeetingPoints = (float) data_get($settings->get('kpi_points_customer_meeting'), 'points', 0.5);
            $successfulReferralPoints = (float) data_get($settings->get('kpi_points_successful_referral'), 'points', 1);
            $workDayPoints = (float) data_get($settings->get('kpi_points_work_day_rate'), 'points', 1);
            $workDaysStep = (int) data_get($settings->get('kpi_points_work_day_rate'), 'days', 5);
            $absencePenalty = (float) data_get($settings->get('kpi_points_absence_penalty'), 'points', 0.5);

            $mappedUsers = $users->map(function ($user) use ($successfulTransactionPoints, $siteTourPoints, $customerMeetingPoints, $successfulReferralPoints, $workDayPoints, $workDaysStep, $absencePenalty) {
                $userTransactions = $user->successful_transactions ?? 0;
                $userTours = $user->site_tours_count ?? 0;
                $userMeetings = $user->customer_meetings_count ?? 0;
                $userReferrals = $user->referrals_count ?? 0;
                $userWorkDays = $user->working_days ?? 0;
                $userAbsences = $user->fixed_schedule_absences ?? 0;

                $kpiPoints = ($userTransactions * $successfulTransactionPoints)
                    + ($userTours * $siteTourPoints)
                    + ($userMeetings * $customerMeetingPoints)
                    + ($userReferrals * $successfulReferralPoints)
                    + ($workDaysStep > 0 ? floor($userWorkDays / $workDaysStep) * $workDayPoints : 0)
                    - ($userAbsences * abs($absencePenalty));

                $user->computed_kpi = $kpiPoints;
                return $user;
            });

            $departments = $mappedUsers->groupBy('department')->map(function ($usersInDept, $deptName) {
                return [
                    'department_name' => $deptName,
                    'total_employees' => $usersInDept->count(),
                    'total_kpi' => (float) $usersInDept->sum('computed_kpi'),
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
