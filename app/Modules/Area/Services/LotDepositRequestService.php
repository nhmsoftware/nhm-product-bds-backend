<?php

declare(strict_types=1);

namespace App\Modules\Area\Services;

use App\Core\Services\ServiceReturn;
use App\Core\Services\BaseService;
use App\Modules\Area\DTO\CreateLotDepositRequestDTO;
use App\Modules\Area\Interfaces\LotDepositRequestRepositoryInterface;
use App\Modules\Area\Interfaces\LotDepositRequestServiceInterface;
use App\Modules\Area\Interfaces\LotRepositoryInterface;
use App\Modules\Area\Models\Enums\LotStatus;
use App\Modules\Area\Models\Enums\LotDepositRequestStatus;
use App\Modules\Area\Events\LotDepositRequested;
use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use App\Modules\Auth\Interfaces\RewardPointHistoryRepositoryInterface;

final class LotDepositRequestService extends BaseService implements LotDepositRequestServiceInterface
{
    public function __construct(
        private readonly LotDepositRequestRepositoryInterface $repository,
        private readonly LotRepositoryInterface $lotRepository,
        private readonly AuthRepositoryInterface $authRepository,
        private readonly RewardPointHistoryRepositoryInterface $rewardPointHistoryRepository
    ) {
    }

    public function create(CreateLotDepositRequestDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $lot = $this->lotRepository->findById($dto->lot_id);
            $this->validate($lot !== null, 'Lô đất không tồn tại.', 404);

            // A1 - Lô đất đã bán
            $this->validate($lot->status !== LotStatus::SOLD, 'Lô đất đã được bán.', 400);

            // Kiểm tra lô đất bị khóa
            $this->validate(!$lot->is_locked, 'Lô đất đã bị khóa.', 403);

            // Chỉ cho phép AVAILABLE hoặc RESERVED
            $this->validate(
                in_array($lot->status, [LotStatus::AVAILABLE, LotStatus::RESERVED], true),
                'Lô đất không ở trạng thái hợp lệ để đặt cọc.',
                400
            );

            // A2 - Lô đất đang có yêu cầu đặt cọc khác
            $hasPending = $this->repository->hasPendingDepositRequestForLot($dto->lot_id);
            $this->validate(!$hasPending, 'Lô đất đang có yêu cầu đặt cọc xử lý.', 400);

            // Create deposit request
            $model = $this->repository->create($dto->toArray());

            // Fire Event
            event(new LotDepositRequested($model));

            return $this->success($model->toArray(), 'Yêu cầu đặt cọc đã được gửi thành công.', 201);
        }, useTransaction: true);
    }

    public function adminGetList(\App\Modules\Area\DTO\FilterLotDepositRequestDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $data = $this->repository->getAdminList($dto);
            return $this->success($data->toArray());
        });
    }

    public function adminGetDetail(string $id): ServiceReturn
    {
        return $this->execute(function () use ($id) {
            $model = $this->repository->findById($id);
            $this->validate($model !== null, 'Yêu cầu đặt cọc không tồn tại.', 404);
            $model->load(['lot.area.project', 'user']);
            return $this->success($model->toArray());
        });
    }

    public function adminApprove(string $id, \App\Modules\Auth\Models\User $user): ServiceReturn
    {
        return $this->execute(function () use ($id, $user) {
            $model = $this->repository->findById($id);
            $this->validate($model !== null, 'Yêu cầu đặt cọc không tồn tại.', 404);
            $this->validate($model->status === LotDepositRequestStatus::PENDING, 'Yêu cầu này không ở trạng thái chờ xác nhận.', 400);

            $model->load('lot.area.project');
            $lot = $model->lot;
            $this->validate($lot !== null, 'Lô đất không tồn tại.', 404);

            if ($user->role !== \App\Modules\Auth\Models\Enums\UserRole::SUPER_ADMIN) {
                $this->validate($lot->area->project->branch === $user->area, 'Bạn không có quyền thực hiện chức năng này.', 403);
            }

            $this->validate(
                in_array($lot->status, [LotStatus::AVAILABLE, LotStatus::RESERVED], true),
                'Lô đất không còn khả dụng.',
                400
            );

            $this->repository->updateById($id, ['status' => LotDepositRequestStatus::APPROVED->value]);
            $this->lotRepository->updateById($lot->id, ['status' => LotStatus::RESERVED->value]);
            
            event(new \App\Modules\Area\Events\LotDepositRequestApproved($model));

            return $this->success(null, 'Duyệt yêu cầu đặt cọc thành công.');
        }, useTransaction: true);
    }

    public function adminReject(string $id, \App\Modules\Auth\Models\User $user, string $reason): ServiceReturn
    {
        return $this->execute(function () use ($id, $user, $reason) {
            $model = $this->repository->findById($id);
            $this->validate($model !== null, 'Yêu cầu đặt cọc không tồn tại.', 404);
            $this->validate($model->status === LotDepositRequestStatus::PENDING, 'Yêu cầu đặt cọc đã được xử lý.', 400);

            $model->load('lot.area.project');
            $lot = $model->lot;
            $this->validate($lot !== null, 'Lô đất không tồn tại.', 404);

            // Kiểm tra phân quyền chi nhánh (A4)
            if ($user->role !== \App\Modules\Auth\Models\Enums\UserRole::SUPER_ADMIN) {
                $this->validate($lot->area->project->branch === $user->area, 'Bạn không có quyền thực hiện chức năng này.', 403);
            }

            $this->repository->updateById($id, [
                'status' => LotDepositRequestStatus::REJECTED->value,
                'reject_reason' => $reason
            ]);
            
            // Fire event
            event(new \App\Modules\Area\Events\LotDepositRequestRejected($model));

            return $this->success(null, 'Từ chối yêu cầu đặt cọc thành công.');
        }, useTransaction: true);
    }

    public function adminConfirmTransaction(string $id, \App\Modules\Auth\Models\User $user): ServiceReturn
    {
        return $this->execute(function () use ($id, $user) {
            $model = $this->repository->findById($id);
            $this->validate($model !== null, 'Lô đất không tồn tại.', 404); // Using 'Lô đất không tồn tại' for missing model to match A3 if needed, or stick to 'Giao dịch không tồn tại'. Wait, A3 says "Lô đất không tồn tại".
            
            // A1 - Giao dịch chưa được duyệt đặt cọc (status < APPROVED or PENDING)
            // A2 - Giao dịch đã được xác nhận trước đó (status == COMPLETED)
            if ($model->status === LotDepositRequestStatus::PENDING) {
                $this->validate(false, 'Giao dịch chưa được duyệt đặt cọc.', 400);
            }
            if ($model->status === LotDepositRequestStatus::COMPLETED) {
                $this->validate(false, 'Giao dịch đã được xác nhận thành công.', 400);
            }
            $this->validate($model->status === LotDepositRequestStatus::APPROVED, 'Giao dịch không ở trạng thái hợp lệ.', 400);

            $model->load('lot.area.project', 'user.employeeProfile');
            $lot = $model->lot;
            $this->validate($lot !== null, 'Lô đất không tồn tại.', 404);

            // A4 - Người dùng không có quyền xác nhận giao dịch
            if ($user->role !== \App\Modules\Auth\Models\Enums\UserRole::SUPER_ADMIN) {
                $this->validate($lot->area->project->branch === $user->area, 'Bạn không có quyền thực hiện chức năng này.', 403);
            }

            // Update status
            $this->repository->updateById($id, ['status' => LotDepositRequestStatus::COMPLETED->value]);
            $this->lotRepository->updateById($lot->id, ['status' => LotStatus::SOLD->value]);

            // Add points to employee
            $this->authRepository->addRewardPointsAndStars($model->user_id, 10, 1);

            // Save history
            $this->rewardPointHistoryRepository->create([
                'user_id' => $model->user_id,
                'points_changed' => 10,
                'stars_changed' => 1,
                'reason' => 'Thưởng giao dịch thành công lô đất ' . $lot->code,
                'related_id' => $id,
            ]);

            // Fire Event
            event(new \App\Modules\Area\Events\TransactionCompleted($model));

            return $this->success(null, 'Xác nhận giao dịch thành công.');
        }, useTransaction: true);
    }
}
