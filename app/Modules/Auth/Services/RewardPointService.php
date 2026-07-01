<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Auth\DTO\GetRewardPointHistoryDTO;
use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use App\Modules\Auth\Interfaces\RewardPointHistoryRepositoryInterface;
use App\Modules\Auth\Models\Enums\UserRole;

/**
 * Handles reward point overview and history.
 */
final class RewardPointService extends BaseService
{
    private const REWARD_ALLOWED_ROLES = [
        'employee',
        'tp_kd',
        'gdkd',
    ];

    public function __construct(
        private readonly AuthRepositoryInterface $authRepository,
        private readonly RewardPointHistoryRepositoryInterface $rewardPointHistoryRepository,
    ) {
    }

    public function getRewardPointOverview(string $userId): ServiceReturn
    {
        return $this->execute(function () use ($userId) {
            $user = $this->authRepository->findById($userId, ['*'], ['employeeProfile']);

            $this->validate($user !== null, 'Không tìm thấy thông tin người dùng.', 404);
            $this->validate(
                $user->role && in_array($user->role->name, self::REWARD_ALLOWED_ROLES, true)
                    && ($user->role->name !== 'employee' || (!empty($user->job_position))),
                'Bạn không có quyền truy cập chức năng này.',
                403
            );

            $ep = $user->employeeProfile;
            $totalPoints = (int) ($ep?->reward_points ?? 0);
            $rank = $this->resolveRewardRank($totalPoints);

            $currentMonthPoints = $this->rewardPointHistoryRepository->calculateCurrentMonthPoints($userId);

            $quarterPoints = $this->rewardPointHistoryRepository->calculateQuarterPoints($userId);
            $targetPoints = 100;
            $quarterProgress = $quarterPoints > 0 ? round(($quarterPoints / $targetPoints) * 100, 2) : 0;
            if ($quarterProgress > 100) {
                $quarterProgress = 100;
            }

            $kpiPoints = app(KpiService::class)->calculateEmployeeKpiPoints(
                $userId,
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString()
            );

            $overview = [
                'total_points' => $totalPoints,
                'kpi_stars' => (int) $kpiPoints,
                'rank' => $rank,
                'current_month_points' => $currentMonthPoints,
                'quarter_progress_percent' => $quarterProgress,
                'quarter_points' => $quarterPoints,
                'quarter_target' => $targetPoints,
            ];

            return $this->success($overview, 'Tải dữ liệu tổng quan thành công.');
        });
    }

    public function getRewardPointHistory(GetRewardPointHistoryDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $user = $this->authRepository->findById($dto->userId);

            $this->validate($user !== null, 'Không tìm thấy thông tin người dùng.', 404);
            $this->validate(
                $user->role && in_array($user->role->name, self::REWARD_ALLOWED_ROLES, true)
                    && ($user->role->name !== 'employee' || (!empty($user->job_position))),
                'Bạn không có quyền truy cập chức năng này.',
                403
            );

            $histories = $this->rewardPointHistoryRepository->getHistoriesPaginated(
                $dto->userId,
                $dto->fromDate,
                $dto->toDate,
                $dto->perPage
            );

            if ($histories->isEmpty() && !$dto->fromDate && !$dto->toDate) {
                return $this->success($histories, 'Chưa có dữ liệu điểm thưởng.');
            }

            if ($histories->isEmpty()) {
                return $this->success($histories, 'Không tìm thấy dữ liệu phù hợp.');
            }

            return $this->success($histories, 'Tải dữ liệu lịch sử điểm thưởng thành công.');
        });
    }

    private function resolveRewardRank(int $totalPoints): array
    {
        if ($totalPoints >= 1500) {
            return ['id' => 4, 'label' => 'Bạch kim'];
        }

        if ($totalPoints >= 800) {
            return ['id' => 3, 'label' => 'Vàng'];
        }

        if ($totalPoints >= 500) {
            return ['id' => 2, 'label' => 'Bạc'];
        }

        return ['id' => 1, 'label' => 'Đồng'];
    }
}
