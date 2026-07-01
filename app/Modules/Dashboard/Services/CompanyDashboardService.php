<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Services;

use App\Core\Interfaces\ServiceReturn;
use App\Core\Services\BaseService;
use App\Modules\Dashboard\Interfaces\CompanyDashboardServiceInterface;
use App\Modules\Dashboard\DTO\ViewCompanyDashboardDTO;
use App\Modules\Auth\Models\User;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use App\Modules\Area\Interfaces\LotDepositRequestRepositoryInterface;
use App\Modules\EmployeeReferral\Interfaces\ReferralHistoryRepositoryInterface;

final class CompanyDashboardService extends BaseService implements CompanyDashboardServiceInterface
{
    public function __construct(
        private readonly AuthRepositoryInterface $authRepository,
        private readonly LotDepositRequestRepositoryInterface $lotDepositRequestRepository,
        private readonly ReferralHistoryRepositoryInterface $referralHistoryRepository
    ) {}
    /**
     * Lấy dữ liệu dashboard tổng quan công ty (UC-111)
     *
     * @param \App\Modules\Dashboard\DTO\ViewCompanyDashboardDTO $dto
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function getCompanyDashboard(ViewCompanyDashboardDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $ceo = $this->authRepository->findById($dto->userId);
            $this->validate($ceo !== null && $ceo->role?->name === 'ceo', 'Bạn không có quyền truy cập chức năng này.', 403);

            // Lọc thời gian
            $month = $dto->month;
            $quarter = $dto->quarter;
            $year = $dto->year;
            $area = $dto->area;
            if ($area && !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $area)) {
                $area = \Illuminate\Support\Facades\DB::table('branches')->where('name', $area)->value('id');
            }

            // --- 1. OVERVIEW METRICS ---
            
            // Tổng nhân sự
            $totalEmployees = $this->authRepository->countEmployees($area);

            // Tổng phòng ban
            $totalDepartments = $this->authRepository->countDepartments($area);

            // Tổng khách hàng (BUYER)
            $totalCustomers = $this->authRepository->countCustomers($area, $month, $quarter, $year);

            // Tổng giới thiệu
            $totalReferrals = $this->referralHistoryRepository->countSuccessfulReferrals($month, $quarter, $year, $area);

            // Giao dịch & Doanh thu
            $transactionStats = $this->lotDepositRequestRepository->getCompanyDashboardTransactionStats($month, $quarter, $year, $area);
            $totalTransactions = $transactionStats['total_transactions'];
            $totalRevenue = $transactionStats['total_revenue'];

            // --- 2. DEPARTMENT STATS & DYNAMIC KPI ---
            $usersForDepts = $this->authRepository->getDepartmentStatsForDashboard($area, $month, $quarter, $year);
            $userIds = $usersForDepts->pluck('id')->toArray();

            $fromDate = null;
            $toDate = null;
            if ($year) {
                if ($month) {
                    $fromDate = \Carbon\Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
                    $toDate = \Carbon\Carbon::create($year, $month, 1)->endOfMonth()->toDateString();
                } elseif ($quarter) {
                    $startMonth = ($quarter - 1) * 3 + 1;
                    $endMonth = $quarter * 3;
                    $fromDate = \Carbon\Carbon::create($year, $startMonth, 1)->startOfMonth()->toDateString();
                    $toDate = \Carbon\Carbon::create($year, $endMonth, 1)->endOfMonth()->toDateString();
                } else {
                    $fromDate = \Carbon\Carbon::create($year, 1, 1)->startOfYear()->toDateString();
                    $toDate = \Carbon\Carbon::create($year, 12, 31)->endOfYear()->toDateString();
                }
            }

            $toursMap = \Illuminate\Support\Facades\DB::table('site_tours')
                ->whereIn('user_id', $userIds)
                ->when($fromDate, fn($q) => $q->whereDate('created_at', '>=', $fromDate))
                ->when($toDate, fn($q) => $q->whereDate('created_at', '<=', $toDate))
                ->selectRaw('user_id, count(*) as count')
                ->groupBy('user_id')
                ->pluck('count', 'user_id');

            $meetingsMap = \Illuminate\Support\Facades\DB::table('customer_meetings')
                ->whereIn('user_id', $userIds)
                ->when($fromDate, fn($q) => $q->whereDate('created_at', '>=', $fromDate))
                ->when($toDate, fn($q) => $q->whereDate('created_at', '<=', $toDate))
                ->selectRaw('user_id, count(*) as count')
                ->groupBy('user_id')
                ->pluck('count', 'user_id');

            $referralsMap = \Illuminate\Support\Facades\DB::table('referral_histories')
                ->whereIn('referrer_id', $userIds)
                ->where('referral_type', 1)
                ->where('status', 2)
                ->when($fromDate, fn($q) => $q->whereDate('created_at', '>=', $fromDate))
                ->when($toDate, fn($q) => $q->whereDate('created_at', '<=', $toDate))
                ->selectRaw('referrer_id as user_id, count(*) as count')
                ->groupBy('referrer_id')
                ->pluck('count', 'user_id');

            $workDaysMap = \Illuminate\Support\Facades\DB::table('attendances')
                ->whereIn('user_id', $userIds)
                ->whereIn('status', [1, 2])
                ->when($fromDate, fn($q) => $q->whereDate('work_date', '>=', $fromDate))
                ->when($toDate, fn($q) => $q->whereDate('work_date', '<=', $toDate))
                ->selectRaw('user_id, count(*) as count')
                ->groupBy('user_id')
                ->pluck('count', 'user_id');

            $absencesMap = \Illuminate\Support\Facades\DB::table('attendances')
                ->whereIn('user_id', $userIds)
                ->where('status', 3)
                ->when($fromDate, fn($q) => $q->whereDate('work_date', '>=', $fromDate))
                ->when($toDate, fn($q) => $q->whereDate('work_date', '<=', $toDate))
                ->selectRaw('user_id, count(*) as count')
                ->groupBy('user_id')
                ->pluck('count', 'user_id');

            $settings = \App\Modules\Area\Models\InventorySetting::pluck('value', 'key');
            $successfulTransactionPoints = (float) data_get($settings->get('kpi_points_successful_transaction'), 'points', 10);
            $siteTourPoints = (float) data_get($settings->get('kpi_points_site_tour'), 'points', 1);
            $customerMeetingPoints = (float) data_get($settings->get('kpi_points_customer_meeting'), 'points', 0.5);
            $successfulReferralPoints = (float) data_get($settings->get('kpi_points_successful_referral'), 'points', 1);
            $workDayPoints = (float) data_get($settings->get('kpi_points_work_day_rate'), 'points', 1);
            $workDaysStep = (int) data_get($settings->get('kpi_points_work_day_rate'), 'days', 5);
            $absencePenalty = (float) data_get($settings->get('kpi_points_absence_penalty'), 'points', 0.5);

            foreach ($usersForDepts as $u) {
                $mId = (string) $u->id;
                $userTransactions = $u->successful_transactions ?? 0;
                $userTours = $toursMap->get($mId, 0);
                $userMeetings = $meetingsMap->get($mId, 0);
                $userReferrals = $referralsMap->get($mId, 0);
                $userWorkDays = $workDaysMap->get($mId, 0);
                $userAbsences = $absencesMap->get($mId, 0);

                $u->computed_kpi = ($userTransactions * $successfulTransactionPoints)
                    + ($userTours * $siteTourPoints)
                    + ($userMeetings * $customerMeetingPoints)
                    + ($userReferrals * $successfulReferralPoints)
                    + ($workDaysStep > 0 ? floor($userWorkDays / $workDaysStep) * $workDayPoints : 0)
                    - ($userAbsences * abs($absencePenalty));
            }

            $totalKpi = (float) $usersForDepts->sum('computed_kpi');
            
            $departmentStats = $usersForDepts->groupBy('department')->map(function ($usersInDept, $deptName) {
                $kpi = (float) $usersInDept->sum('computed_kpi');
                $transactions = (int) $usersInDept->sum('successful_transactions');
                $revenue = (int) $usersInDept->sum(function ($u) {
                    return $u->lotDepositRequests->sum(fn($req) => $req->lot ? $req->lot->price : 0);
                });

                return [
                    'department_name' => $deptName,
                    'total_kpi' => $kpi,
                    'successful_transactions' => $transactions,
                    'total_revenue' => $revenue,
                ];
            })->values();

            // --- 3. LEADERBOARDS ---
            $topDepartments = $departmentStats->sortByDesc('total_kpi')->take(5)->values()->toArray();
            
            $topEmployees = $usersForDepts->map(function ($u) {
                return [
                    'user_id' => $u->id,
                    'name' => $u->name,
                    'department' => $u->department,
                    'job_position' => $u->job_position,
                    'total_kpi' => $u->computed_kpi,
                ];
            })->sortByDesc('total_kpi')->take(5)->values()->toArray();

            $data = [
                'overview' => [
                    'total_employees' => $totalEmployees,
                    'total_departments' => $totalDepartments,
                    'total_transactions' => $totalTransactions,
                    'total_revenue' => $totalRevenue,
                    'total_customers' => $totalCustomers,
                    'total_referrals' => $totalReferrals,
                    'total_kpi' => $totalKpi,
                ],
                'department_stats' => $departmentStats->toArray(),
                'leaderboards' => [
                    'top_employees_by_kpi' => $topEmployees,
                    'top_departments_by_kpi' => $topDepartments,
                ]
            ];

            return $this->success($data, 'Tải báo cáo dashboard tổng quan thành công.');
        }, useTransaction: false);
    }
}
