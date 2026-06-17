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
            $this->validate($ceo !== null && $ceo->role === UserRole::CEO, 'Bạn không có quyền truy cập chức năng này.', 403);

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

            // Tổng KPI
            $totalKpi = $this->authRepository->countKpiStars($area);

            // --- 2. DEPARTMENT STATS ---
            $usersForDepts = $this->authRepository->getDepartmentStatsForDashboard($area, $month, $quarter, $year);
            
            $departmentStats = $usersForDepts->groupBy('department')->map(function ($usersInDept, $deptName) {
                $kpi = (int) $usersInDept->sum(fn($u) => $u->employeeProfile ? $u->employeeProfile->kpi_stars : 0);
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
                    'total_kpi' => $u->employeeProfile ? $u->employeeProfile->kpi_stars : 0,
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
