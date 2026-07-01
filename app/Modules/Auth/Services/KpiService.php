<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Auth\DTO\GetDepartmentRankingDTO;
use App\Modules\Auth\DTO\GetEmployeeKpiDTO;
use App\Modules\Auth\DTO\GetTeamKpiDTO;
use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use App\Modules\Auth\Models\Enums\UserRole;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Handles all KPI operations: team KPI, leaderboard, employee details, department ranking.
 */
final class KpiService extends BaseService
{
    public function __construct(
        private readonly AuthRepositoryInterface $authRepository,
        private readonly \App\Modules\Area\Interfaces\LotDepositRequestRepositoryInterface $lotDepositRequestRepository,
        private readonly \App\Modules\SiteTour\Interfaces\SiteTourRepositoryInterface $siteTourRepository,
        private readonly \App\Modules\CustomerMeeting\Interfaces\CustomerMeetingRepositoryInterface $customerMeetingRepository,
        private readonly \App\Modules\EmployeeReferral\Interfaces\ReferralHistoryRepositoryInterface $referralHistoryRepository,
        private readonly \App\Modules\Attendance\Interfaces\AttendanceRepositoryInterface $attendanceRepository,
    ) {
    }

    public function getTeamKpiOverview(GetTeamKpiDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $user = $this->authRepository->findById($dto->userId);

            $this->validate($user !== null, 'Không tìm thấy thông tin người dùng.', 404);

            $allowedRoles = ['tp_kd', 'gdkd'];
            $this->validate(
                $user->role && in_array($user->role->name, $allowedRoles, true),
                'Bạn không có quyền truy cập chức năng này.',
                403
            );

            $members = $this->authRepository->getScopedActiveEmployees($user);

            if ($members->isEmpty()) {
                return $this->success([
                    'total_kpi_points' => 0,
                    'total_transactions' => 0,
                    'total_tours' => 0,
                    'total_meetings' => 0,
                    'total_referrals' => 0,
                ], 'Chưa có dữ liệu KPI đội nhóm.');
            }

            $userIds = $members->pluck('id')->toArray();

            $totalTransactions = $this->lotDepositRequestRepository->countCompletedTransactions($userIds, $dto->fromDate, $dto->toDate);
            $totalTours = $this->siteTourRepository->countSiteTours($userIds, $dto->fromDate, $dto->toDate);
            $totalMeetings = $this->customerMeetingRepository->countCustomerMeetings($userIds, $dto->fromDate, $dto->toDate);
            $totalReferrals = $this->referralHistoryRepository->countSuccessfulReferralsForUsers($userIds, $dto->fromDate, $dto->toDate);

            $workDaysQuery = $this->attendanceRepository->countWorkDaysByUsers($userIds, $dto->fromDate, $dto->toDate);
            $absencesQuery = $this->attendanceRepository->countFixedScheduleAbsencesByUsers($userIds, $dto->fromDate, $dto->toDate);
            $transactionsByEmployee = $this->lotDepositRequestRepository->countCompletedTransactionsByUsers($userIds, $dto->fromDate, $dto->toDate);
            $toursByEmployee = $this->siteTourRepository->countSiteToursByUsers($userIds, $dto->fromDate, $dto->toDate);
            $meetingsByEmployee = $this->customerMeetingRepository->countCustomerMeetingsByUsers($userIds, $dto->fromDate, $dto->toDate);
            $referralsByEmployee = $this->referralHistoryRepository->countSuccessfulReferralsByUsers($userIds, $dto->fromDate, $dto->toDate);

            $totalKpiPoints = 0;
            foreach ($userIds as $mId) {
                $mTransactions = $transactionsByEmployee->get($mId, 0);
                $mTours = $toursByEmployee->get($mId, 0);
                $mMeetings = $meetingsByEmployee->get($mId, 0);
                $mReferrals = $referralsByEmployee->get($mId, 0);
                $mWorkDays = $workDaysQuery->get($mId, 0);
                $mAbsences = $absencesQuery->get($mId, 0);

                $kpi = ($mTransactions * 10)
                    + ($mTours * 1)
                    + ($mMeetings * 0.5)
                    + ($mReferrals * 1)
                    + floor($mWorkDays / 5)
                    - ($mAbsences * 0.5);

                $totalKpiPoints += $kpi;
            }

            return $this->success([
                'total_kpi_points' => $totalKpiPoints,
                'total_transactions' => $totalTransactions,
                'total_tours' => $totalTours,
                'total_meetings' => $totalMeetings,
                'total_referrals' => $totalReferrals,
            ], 'Tải dữ liệu tổng quan KPI thành công.');
        });
    }

    public function getTeamKpiLeaderboard(GetTeamKpiDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $user = $this->authRepository->findById($dto->userId);

            $this->validate($user !== null, 'Không tìm thấy thông tin người dùng.', 404);

            $allowedRoles = ['tp_kd', 'gdkd'];
            $this->validate(
                $user->role && in_array($user->role->name, $allowedRoles, true),
                'Bạn không có quyền truy cập chức năng này.',
                403
            );

            $members = $this->authRepository->getFilteredScopedActiveEmployees(
                $user, $dto->search, $dto->jobPosition, true
            );

            if ($members->isEmpty()) {
                $paginated = new LengthAwarePaginator([], 0, $dto->perPage);
                return $this->success($paginated, 'Không tìm thấy dữ liệu phù hợp.');
            }

            $userIds = $members->pluck('id')->toArray();

            $transactions = $this->lotDepositRequestRepository->countCompletedTransactionsByUsers($userIds, $dto->fromDate, $dto->toDate);
            $tours = $this->siteTourRepository->countSiteToursByUsers($userIds, $dto->fromDate, $dto->toDate);
            $meetings = $this->customerMeetingRepository->countCustomerMeetingsByUsers($userIds, $dto->fromDate, $dto->toDate);
            $referrals = $this->referralHistoryRepository->countSuccessfulReferralsByUsers($userIds, $dto->fromDate, $dto->toDate);
            $workDays = $this->attendanceRepository->countWorkDaysByUsers($userIds, $dto->fromDate, $dto->toDate);
            $absences = $this->attendanceRepository->countFixedScheduleAbsencesByUsers($userIds, $dto->fromDate, $dto->toDate);

            $settings = $this->loadKpiSettings();

            $rankedList = $members->map(function ($member) use ($transactions, $tours, $meetings, $referrals, $workDays, $absences, $settings) {
                return $this->buildEmployeeRankEntry($member, $transactions, $tours, $meetings, $referrals, $workDays, $absences, $settings);
            });

            $sorted = $this->sortByKpi($rankedList);
            $ranked = $this->assignRanks($sorted);
            $paginated = $this->paginate($ranked, $dto->perPage);

            return $this->success($paginated, 'Tải bảng xếp hạng KPI thành công.');
        });
    }

    public function getEmployeeKpiDetails(GetEmployeeKpiDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $manager = $this->authRepository->findById($dto->managerId);
            $this->validate($manager !== null, 'Không tìm thấy thông tin người quản lý.', 404);

            $allowedRoles = ['tp_kd', 'gdkd'];
            $this->validate(
                $manager->role && in_array($manager->role->name, $allowedRoles, true),
                'Bạn không có quyền truy cập chức năng này.',
                403
            );

            $employee = $this->authRepository->findById($dto->employeeId);
            $this->validate($employee !== null, 'Không tìm thấy thông tin nhân viên.', 404);

            if ($manager->role?->name === 'tp_kd') {
                $this->validate(
                    $employee->department_id === $manager->department_id,
                    'Bạn không có quyền truy cập chức năng này.',
                    403
                );
            } elseif ($manager->role?->name === 'gdkd') {
                $this->validate(
                    $employee->branch_id === $manager->branch_id,
                    'Bạn không có quyền truy cập chức năng này.',
                    403
                );
            }

            $settings = $this->loadKpiSettings();
            $kpiPoints = $this->calculateEmployeeKpiPoints($dto->employeeId, $dto->fromDate, $dto->toDate);
            $totalStars = (int) $kpiPoints;

            $userTransactions = $this->lotDepositRequestRepository->countCompletedTransactions($dto->employeeId, $dto->fromDate, $dto->toDate);
            $userTours = $this->siteTourRepository->countSiteTours($dto->employeeId, $dto->fromDate, $dto->toDate);
            $userMeetings = $this->customerMeetingRepository->countCustomerMeetings($dto->employeeId, $dto->fromDate, $dto->toDate);
            $userReferrals = $this->referralHistoryRepository->countSuccessfulReferralsForUsers($dto->employeeId, $dto->fromDate, $dto->toDate);
            $userWorkDays = $this->attendanceRepository->countWorkDays($dto->employeeId, $dto->fromDate, $dto->toDate);
            $userAbsences = $this->attendanceRepository->countFixedScheduleAbsences($dto->employeeId, $dto->fromDate, $dto->toDate);

            $rewardPointHistoryRepository = app(\App\Modules\Auth\Interfaces\RewardPointHistoryRepositoryInterface::class);
            $history = $rewardPointHistoryRepository->getHistoriesPaginated(
                $dto->employeeId, $dto->fromDate, $dto->toDate, $dto->perPage
            );

            $result = [
                'employee' => [
                    'id' => (string) $employee->id,
                    'staff_code' => $employee->staff_code,
                    'name' => $employee->name,
                    'job_position' => $employee->job_position,
                    'job_position_id' => $employee->job_position_id,
                    'avatar' => $employee->avatar,
                    'department' => $employee->department,
                    'department_id' => $employee->department_id,
                    'area' => $employee->area,
                    'branch_id' => $employee->branch_id,
                    'branch_name' => $employee->area,
                ],
                'kpi_summary' => [
                    'total_kpi_points' => $kpiPoints,
                    'kpi_stars' => $totalStars,
                    'transactions_count' => $userTransactions,
                    'tours_count' => $userTours,
                    'meetings_count' => $userMeetings,
                    'referrals_count' => $userReferrals,
                    'work_days' => $userWorkDays,
                    'absences' => $userAbsences,
                ],
                'reward_history' => $history,
            ];

            return $this->success($result, 'Tải chi tiết KPI nhân viên thành công.');
        });
    }

    public function getDepartmentRanking(GetDepartmentRankingDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $user = $this->authRepository->findById($dto->userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin người dùng.', 404);

            $allowedRoles = ['tp_kd', 'gdkd', 'ceo'];
            $this->validate(
                $user->role && in_array($user->role->name, $allowedRoles, true),
                'Bạn không có quyền truy cập chức năng này.',
                403
            );

            $hasAnyDept = $this->authRepository->hasActiveEmployeesWithDepartment();
            if (!$hasAnyDept) {
                $paginated = new LengthAwarePaginator([], 0, $dto->perPage);
                return $this->success($paginated, 'Chưa có dữ liệu xếp hạng phòng ban.');
            }

            $branchId = $dto->area;
            if ($branchId && !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $branchId)) {
                $branchId = \Illuminate\Support\Facades\DB::table('branches')->where('name', $branchId)->value('id');
            }

            $users = $this->authRepository->getActiveEmployeesWithDepartment($branchId);

            if ($users->isEmpty()) {
                $paginated = new LengthAwarePaginator([], 0, $dto->perPage);
                return $this->success($paginated, 'Không tìm thấy dữ liệu phù hợp.');
            }

            [$fromDate, $toDate] = $this->resolveDateRange($dto->year, $dto->month, $dto->quarter);

            $settings = $this->loadKpiSettings();
            $usersByDept = $users->groupBy('department');
            $deptData = [];

            foreach ($usersByDept as $deptName => $deptUsers) {
                $userIds = $deptUsers->pluck('id')->toArray();

                $deptTransactions = $this->lotDepositRequestRepository->countCompletedTransactions($userIds, $fromDate, $toDate);
                $deptTours = $this->siteTourRepository->countSiteTours($userIds, $fromDate, $toDate);
                $deptMeetings = $this->customerMeetingRepository->countCustomerMeetings($userIds, $fromDate, $toDate);
                $deptReferrals = $this->referralHistoryRepository->countSuccessfulReferralsForUsers($userIds, $fromDate, $toDate);

                $workDaysMap = $this->attendanceRepository->countWorkDaysByUsers($userIds, $fromDate, $toDate);
                $absencesMap = $this->attendanceRepository->countFixedScheduleAbsencesByUsers($userIds, $fromDate, $toDate);

                $totalKpiPoints = 0;
                $totalStars = 0;

                foreach ($userIds as $uId) {
                    $uTransactions = $this->lotDepositRequestRepository->countCompletedTransactions($uId, $fromDate, $toDate);
                    $uTours = $this->siteTourRepository->countSiteTours($uId, $fromDate, $toDate);
                    $uMeetings = $this->customerMeetingRepository->countCustomerMeetings($uId, $fromDate, $toDate);
                    $uReferrals = $this->referralHistoryRepository->countSuccessfulReferralsForUsers($uId, $fromDate, $toDate);
                    $uWorkDays = $workDaysMap->get($uId, 0);
                    $uAbsences = $absencesMap->get($uId, 0);

                    $kpi = ($uTransactions * $settings['successfulTransaction'])
                        + ($uTours * $settings['siteTour'])
                        + ($uMeetings * $settings['customerMeeting'])
                        + ($uReferrals * $settings['successfulReferral'])
                        + ($settings['workDaysStep'] > 0 ? floor($uWorkDays / $settings['workDaysStep']) * $settings['workDay'] : 0)
                        - ($uAbsences * abs($settings['absencePenalty']));

                    $totalKpiPoints += $kpi;
                    $totalStars += $kpi;
                }

                $deptData[] = [
                    'department' => $deptName,
                    'total_kpi_points' => $totalKpiPoints,
                    'successful_transactions' => $deptTransactions,
                    'kpi_stars' => $totalStars,
                    'total_tours' => $deptTours,
                    'total_meetings' => $deptMeetings,
                    'total_referrals' => $deptReferrals,
                ];
            }

            $sortedDepts = collect($deptData)->sort(function ($a, $b) {
                if ($b['total_kpi_points'] <=> $a['total_kpi_points']) {
                    return $b['total_kpi_points'] <=> $a['total_kpi_points'];
                }
                if ($b['successful_transactions'] <=> $a['successful_transactions']) {
                    return $b['successful_transactions'] <=> $a['successful_transactions'];
                }
                return strcmp($a['department'], $b['department']);
            })->values();

            $rankedDepts = $this->assignRanks($sortedDepts);
            $paginated = $this->paginate($rankedDepts, $dto->perPage);

            return $this->success($paginated, 'Tải bảng xếp hạng phòng ban thành công.');
        });
    }

    public function getDepartmentKpiDetails(string $departmentName, GetDepartmentRankingDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($departmentName, $dto) {
            $user = $this->authRepository->findById($dto->userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin người dùng.', 404);

            $allowedRoles = ['tp_kd', 'gdkd', 'ceo'];
            $this->validate(
                $user->role && in_array($user->role->name, $allowedRoles, true),
                'Bạn không có quyền truy cập chức năng này.',
                403
            );

            [$fromDate, $toDate] = $this->resolveDateRange($dto->year, $dto->month, $dto->quarter);

            $members = $this->authRepository->getActiveEmployeesByDepartment($departmentName);

            if ($members->isEmpty()) {
                return $this->success(null, 'Không tìm thấy dữ liệu phù hợp.');
            }

            $userIds = $members->pluck('id')->toArray();

            $totalTransactions = $this->lotDepositRequestRepository->countCompletedTransactions($userIds, $fromDate, $toDate);
            $totalTours = $this->siteTourRepository->countSiteTours($userIds, $fromDate, $toDate);
            $totalMeetings = $this->customerMeetingRepository->countCustomerMeetings($userIds, $fromDate, $toDate);
            $totalReferrals = $this->referralHistoryRepository->countSuccessfulReferralsForUsers($userIds, $fromDate, $toDate);

            $workDaysMap = $this->attendanceRepository->countWorkDaysByUsers($userIds, $fromDate, $toDate);
            $absencesMap = $this->attendanceRepository->countFixedScheduleAbsencesByUsers($userIds, $fromDate, $toDate);

            $settings = $this->loadKpiSettings();

            $rankedList = $members->map(function ($member) use ($workDaysMap, $absencesMap, $fromDate, $toDate, $settings) {
                $mId = (string) $member->id;

                $uTransactions = $this->lotDepositRequestRepository->countCompletedTransactions($mId, $fromDate, $toDate);
                $uTours = $this->siteTourRepository->countSiteTours($mId, $fromDate, $toDate);
                $uMeetings = $this->customerMeetingRepository->countCustomerMeetings($mId, $fromDate, $toDate);
                $uReferrals = $this->referralHistoryRepository->countSuccessfulReferralsForUsers($mId, $fromDate, $toDate);
                $uWorkDays = $workDaysMap->get($mId, 0);
                $uAbsences = $absencesMap->get($mId, 0);

                $kpiPoints = ($uTransactions * $settings['successfulTransaction'])
                    + ($uTours * $settings['siteTour'])
                    + ($uMeetings * $settings['customerMeeting'])
                    + ($uReferrals * $settings['successfulReferral'])
                    + ($settings['workDaysStep'] > 0 ? floor($uWorkDays / $settings['workDaysStep']) * $settings['workDay'] : 0)
                    - ($uAbsences * abs($settings['absencePenalty']));

                return [
                    'id' => $mId,
                    'staff_code' => $member->staff_code,
                    'name' => $member->name,
                    'job_position' => $member->job_position,
                    'avatar' => $member->avatar,
                    'total_kpi_points' => $kpiPoints,
                    'successful_transactions' => $uTransactions,
                    'kpi_stars' => (int) $kpiPoints,
                ];
            });

            $sorted = $this->sortByKpi($rankedList);
            $ranked = $this->assignRanks($sorted);

            $totalKpiPoints = $ranked->sum('total_kpi_points');
            $totalStars = $ranked->sum('kpi_stars');

            return $this->success([
                'department' => $departmentName,
                'kpi_summary' => [
                    'total_kpi_points' => $totalKpiPoints,
                    'total_transactions' => $totalTransactions,
                    'total_tours' => $totalTours,
                    'total_meetings' => $totalMeetings,
                    'total_referrals' => $totalReferrals,
                    'kpi_stars' => $totalStars,
                ],
                'employee_ranking' => $ranked,
            ], 'Tải chi tiết KPI phòng ban thành công.');
        });
    }

    public function calculateEmployeeKpiPoints(string $employeeId, ?string $fromDate = null, ?string $toDate = null): float
    {
        $userTransactions = $this->lotDepositRequestRepository->countCompletedTransactions($employeeId, $fromDate, $toDate);
        $userTours = $this->siteTourRepository->countSiteTours($employeeId, $fromDate, $toDate);
        $userMeetings = $this->customerMeetingRepository->countCustomerMeetings($employeeId, $fromDate, $toDate);
        $userReferrals = $this->referralHistoryRepository->countSuccessfulReferralsForUsers($employeeId, $fromDate, $toDate);
        $userWorkDays = $this->attendanceRepository->countWorkDays($employeeId, $fromDate, $toDate);
        $userAbsences = $this->attendanceRepository->countFixedScheduleAbsences($employeeId, $fromDate, $toDate);

        $settings = $this->loadKpiSettings();

        return ($userTransactions * $settings['successfulTransaction'])
            + ($userTours * $settings['siteTour'])
            + ($userMeetings * $settings['customerMeeting'])
            + ($userReferrals * $settings['successfulReferral'])
            + ($settings['workDaysStep'] > 0 ? floor($userWorkDays / $settings['workDaysStep']) * $settings['workDay'] : 0)
            - ($userAbsences * abs($settings['absencePenalty']));
    }

    private function loadKpiSettings(): array
    {
        $settings = \App\Modules\Area\Models\InventorySetting::pluck('value', 'key');

        return [
            'successfulTransaction' => (float) data_get($settings->get('kpi_points_successful_transaction'), 'points', 10),
            'siteTour' => (float) data_get($settings->get('kpi_points_site_tour'), 'points', 1),
            'customerMeeting' => (float) data_get($settings->get('kpi_points_customer_meeting'), 'points', 0.5),
            'successfulReferral' => (float) data_get($settings->get('kpi_points_successful_referral'), 'points', 1),
            'workDay' => (float) data_get($settings->get('kpi_points_work_day_rate'), 'points', 1),
            'workDaysStep' => (int) data_get($settings->get('kpi_points_work_day_rate'), 'days', 5),
            'absencePenalty' => (float) data_get($settings->get('kpi_points_absence_penalty'), 'points', 0.5),
        ];
    }

    private function resolveDateRange(?int $year, ?int $month, ?int $quarter): array
    {
        $year = $year ?? (int) now()->year;

        if ($month) {
            return [
                Carbon::create($year, $month, 1)->startOfMonth()->toDateString(),
                Carbon::create($year, $month, 1)->endOfMonth()->toDateString(),
            ];
        }

        if ($quarter) {
            $startMonth = ($quarter - 1) * 3 + 1;
            return [
                Carbon::create($year, $startMonth, 1)->startOfMonth()->toDateString(),
                Carbon::create($year, $startMonth + 2, 1)->endOfMonth()->toDateString(),
            ];
        }

        return [null, null];
    }

    private function buildEmployeeRankEntry(
        object $member,
        $transactions, $tours, $meetings, $referrals, $workDays, $absences,
        array $settings
    ): array {
        $mId = (string) $member->id;

        return [
            'id' => $mId,
            'staff_code' => $member->staff_code,
            'name' => $member->name,
            'job_position' => $member->job_position,
            'avatar' => $member->avatar,
            'total_kpi_points' => ($transactions->get($mId, 0) * $settings['successfulTransaction'])
                + ($tours->get($mId, 0) * $settings['siteTour'])
                + ($meetings->get($mId, 0) * $settings['customerMeeting'])
                + ($referrals->get($mId, 0) * $settings['successfulReferral'])
                + ($settings['workDaysStep'] > 0 ? floor($workDays->get($mId, 0) / $settings['workDaysStep']) * $settings['workDay'] : 0)
                - ($absences->get($mId, 0) * abs($settings['absencePenalty'])),
            'successful_transactions' => $transactions->get($mId, 0),
            'kpi_stars' => (int)(($transactions->get($mId, 0) * $settings['successfulTransaction'])
                + ($tours->get($mId, 0) * $settings['siteTour'])
                + ($meetings->get($mId, 0) * $settings['customerMeeting'])
                + ($referrals->get($mId, 0) * $settings['successfulReferral'])
                + ($settings['workDaysStep'] > 0 ? floor($workDays->get($mId, 0) / $settings['workDaysStep']) * $settings['workDay'] : 0)
                - ($absences->get($mId, 0) * abs($settings['absencePenalty']))),
        ];
    }

    private function sortByKpi($rankedList)
    {
        return $rankedList->sort(function ($a, $b) {
            if ($b['total_kpi_points'] <=> $a['total_kpi_points']) {
                return $b['total_kpi_points'] <=> $a['total_kpi_points'];
            }
            if ($b['successful_transactions'] <=> $a['successful_transactions']) {
                return $b['successful_transactions'] <=> $a['successful_transactions'];
            }
            return strcmp($a['name'], $b['name']);
        })->values();
    }

    private function assignRanks($sorted)
    {
        return $sorted->map(fn ($item, $index) => array_merge($item, ['rank' => $index + 1]));
    }

    private function paginate($ranked, int $perPage): LengthAwarePaginator
    {
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $slice = $ranked->slice(($currentPage - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $slice,
            $ranked->count(),
            $perPage,
            $currentPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
    }
}
