<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Services;

use App\Core\Interfaces\ServiceReturn;
use App\Core\Services\BaseService;
use App\Modules\Dashboard\Interfaces\RevenueReportServiceInterface;
use App\Modules\Dashboard\DTO\ViewRevenueReportDTO;
use App\Modules\Auth\Models\User;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Modules\Area\Models\LotDepositRequest;
use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use App\Modules\Area\Interfaces\LotDepositRequestRepositoryInterface;
use Carbon\Carbon;

final class RevenueReportService extends BaseService implements RevenueReportServiceInterface
{
    public function __construct(
        private readonly AuthRepositoryInterface $authRepository,
        private readonly LotDepositRequestRepositoryInterface $lotDepositRequestRepository
    ) {}
    /**
     * Lấy báo cáo doanh thu công ty (UC-112)
     *
     * @param \App\Modules\Dashboard\DTO\ViewRevenueReportDTO $dto
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function getRevenueReports(ViewRevenueReportDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $ceo = $this->authRepository->findById($dto->userId);
            $this->validate($ceo !== null && $ceo->role === UserRole::CEO, 'Bạn không có quyền truy cập chức năng này.', 403);

            $transactions = $this->lotDepositRequestRepository->getRevenueReportData(
                $dto->startDate,
                $dto->endDate,
                $dto->department,
                $dto->projectId,
                $dto->area
            );

            // 1. Tổng quan doanh thu
            $totalRevenue = (int) $transactions->sum('price');
            $totalTransactions = $transactions->count();

            // 2. Doanh thu theo phòng ban
            $byDepartment = $transactions->groupBy('department')->map(function ($group, $dept) {
                return [
                    'department_name' => $dept ?: 'Không xác định',
                    'revenue' => (int) $group->sum('price'),
                    'transactions_count' => $group->count()
                ];
            })->values()->toArray();

            // 3. Doanh thu theo dự án
            $byProject = $transactions->groupBy('project_id')->map(function ($group, $projectId) {
                return [
                    'project_id' => $projectId,
                    'project_name' => $group->first()->project_name ?: 'Không xác định',
                    'revenue' => (int) $group->sum('price'),
                    'transactions_count' => $group->count()
                ];
            })->values()->toArray();

            // 4. Doanh thu theo nhân viên
            $byEmployee = $transactions->groupBy('user_id')->map(function ($group, $userId) {
                return [
                    'user_id' => $userId,
                    'user_name' => $group->first()->user_name,
                    'revenue' => (int) $group->sum('price'),
                    'transactions_count' => $group->count()
                ];
            })->values()->toArray();

            // 5. Biểu đồ doanh thu
            $chartByMonth = $transactions->groupBy(function ($item) {
                return Carbon::parse($item->created_at)->format('Y-m');
            })->map(function ($group, $key) {
                return ['label' => $key, 'revenue' => (int) $group->sum('price')];
            })->values()->sortBy('label')->values()->toArray();

            $chartByQuarter = $transactions->groupBy(function ($item) {
                $date = Carbon::parse($item->created_at);
                return $date->year . '-Q' . $date->quarter;
            })->map(function ($group, $key) {
                return ['label' => $key, 'revenue' => (int) $group->sum('price')];
            })->values()->sortBy('label')->values()->toArray();

            $chartByYear = $transactions->groupBy(function ($item) {
                return Carbon::parse($item->created_at)->format('Y');
            })->map(function ($group, $key) {
                return ['label' => $key, 'revenue' => (int) $group->sum('price')];
            })->values()->sortBy('label')->values()->toArray();

            $data = [
                'overview' => [
                    'total_revenue' => $totalRevenue,
                    'total_transactions' => $totalTransactions,
                ],
                'by_department' => $byDepartment,
                'by_project' => $byProject,
                'by_employee' => $byEmployee,
                'charts' => [
                    'by_month' => $chartByMonth,
                    'by_quarter' => $chartByQuarter,
                    'by_year' => $chartByYear,
                ]
            ];

            return $this->success($data, 'Tải báo cáo doanh thu thành công.');
        }, useTransaction: false);
    }
}
