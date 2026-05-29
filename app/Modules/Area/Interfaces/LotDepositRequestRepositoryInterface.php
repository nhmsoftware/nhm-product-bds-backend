<?php

declare(strict_types=1);

namespace App\Modules\Area\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Area\Models\LotDepositRequest;

interface LotDepositRequestRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Kiểm tra xem lô đất có yêu cầu đặt cọc nào đang xử lý không.
     *
     * @param string $lotId
     * @return bool
     */
    public function hasPendingDepositRequestForLot(string $lotId): bool;

    /**
     * Lấy danh sách yêu cầu đặt cọc cho Admin/Giám đốc
     *
     * @param \App\Modules\Area\DTO\FilterLotDepositRequestDTO $dto
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAdminList(\App\Modules\Area\DTO\FilterLotDepositRequestDTO $dto): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    /**
     * Lấy dữ liệu báo cáo doanh thu.
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @param string|null $department
     * @param string|null $projectId
     * @param string|null $area
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRevenueReportData(
        ?string $startDate,
        ?string $endDate,
        ?string $department,
        ?string $projectId,
        ?string $area
    ): \Illuminate\Database\Eloquent\Collection;

    /**
     * Lấy thống kê giao dịch cho Company Dashboard.
     * Trả về mảng chứa 'total_transactions' và 'total_revenue'.
     */
    public function getCompanyDashboardTransactionStats(
        ?int $month,
        ?int $quarter,
        ?int $year,
        ?string $area
    ): array;

    /**
     * Đếm số giao dịch công chứng thành công theo userIds và khoảng thời gian.
     */
    public function countCompletedTransactions(
        array|string $userIds,
        ?string $fromDate,
        ?string $toDate
    ): int;

    public function countCompletedTransactionsByUsers(
        array $userIds,
        ?string $fromDate,
        ?string $toDate
    ): \Illuminate\Support\Collection;
}
