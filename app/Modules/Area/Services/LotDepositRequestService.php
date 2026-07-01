<?php

declare(strict_types=1);

namespace App\Modules\Area\Services;

use App\Core\Services\ServiceReturn;
use App\Core\Services\BaseService;
use App\Modules\Area\DTO\CreateLotDepositRequestDTO;
use App\Modules\Area\Interfaces\LotDepositRequestRepositoryInterface;
use App\Modules\Area\Interfaces\LotDepositRequestServiceInterface;
use App\Modules\Area\Interfaces\LotRepositoryInterface;
use App\Modules\Area\Interfaces\LotLockRequestRepositoryInterface;
use App\Modules\Area\Models\Enums\LotStatus;
use App\Modules\Area\Models\Enums\LotDepositRequestStatus;
use App\Modules\Area\Models\Enums\LotLockRequestStatus;
use App\Modules\Area\Models\Lot;
use App\Modules\Area\Events\LotDepositRequested;
use App\Modules\Area\Events\LotLocked;
use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use App\Modules\Auth\Interfaces\RewardPointHistoryRepositoryInterface;

final class LotDepositRequestService extends BaseService implements LotDepositRequestServiceInterface
{
    public function __construct(
        private readonly LotDepositRequestRepositoryInterface $repository,
        private readonly LotRepositoryInterface $lotRepository,
        private readonly LotLockRequestRepositoryInterface $lotLockRequestRepository,
        private readonly AuthRepositoryInterface $authRepository,
        private readonly RewardPointHistoryRepositoryInterface $rewardPointHistoryRepository
    ) {
    }

    public function create(CreateLotDepositRequestDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            /**
             * Khóa row lô đất ngay đầu transaction để tránh 2 user cùng bấm lock/cọc
             * trong cùng một khoảng thời gian và cùng vượt qua bước kiểm tra trạng thái.
             */
            $lot = Lot::query()
                ->where('id', $dto->lot_id)
                ->lockForUpdate()
                ->first();
            $this->validate($lot !== null, 'Lô đất không tồn tại.', 404);
            $lot->load('area');

            if ($lot->area && $lot->area->is_locked) {
                $this->throw('Khu đất đã bị khóa. Không thể thực hiện thao tác trên bảng hàng.', 403);
            }

            $this->validate($lot->status !== LotStatus::SOLD, 'Lô đất đã được bán.', 400);
            $this->validate($lot->status !== LotStatus::UNAVAILABLE, 'Lô đất không khả dụng.', 400);

            $activeDepositRequest = $this->repository->findActiveByLotId($dto->lot_id);
            if ($activeDepositRequest !== null) {
                $message = (string) $activeDepositRequest->user_id === (string) $dto->user_id
                    ? 'Bạn đã gửi yêu cầu đặt cọc lô đất này.'
                    : 'Lô đất đang có yêu cầu đặt cọc của người khác.';
                $this->throw($message, 400);
            }

            $activeLockRequest = $this->lotLockRequestRepository->findActiveByLotId($dto->lot_id);
            if ($activeLockRequest !== null && (string) $activeLockRequest->user_id !== (string) $dto->user_id) {
                $this->throw('Lô đất đang được người khác lock.', 403);
            }

            if ($lot->is_locked && $activeLockRequest === null) {
                $this->throw('Lô đất đã bị khóa.', 403);
            }

            if ($lot->status === LotStatus::RESERVED && $activeLockRequest === null) {
                $this->throw('Lô đất đang được giữ chỗ.', 400);
            }

            $lockRequest = $activeLockRequest;
            if ($lockRequest === null) {
                $lockRequest = $this->lotLockRequestRepository->create([
                    'lot_id' => $dto->lot_id,
                    'user_id' => $dto->user_id,
                    'reason' => $dto->reason ?: 'Tự động lock khi gửi yêu cầu đặt cọc.',
                    'status' => LotLockRequestStatus::APPROVED->value,
                    'expires_at' => null,
                    'approved_by' => $dto->user_id,
                    'approved_at' => now(),
                ]);
            } elseif ($lockRequest->status !== LotLockRequestStatus::APPROVED) {
                $this->lotLockRequestRepository->updateById((string) $lockRequest->id, [
                    'status' => LotLockRequestStatus::APPROVED->value,
                    'approved_by' => $dto->user_id,
                    'approved_at' => now(),
                    'expires_at' => null,
                ]);
                $lockRequest->refresh();
            }

            $this->lotRepository->updateById($lot->id, [
                'status' => LotStatus::RESERVED->value,
                'is_locked' => true,
            ]);
            $lot->refresh();

            $model = $this->repository->create($dto->toArray());

            event(new LotLocked($lot, $lockRequest));
            event(new LotDepositRequested($model));

            return $this->success([
                ...$model->toArray(),
                'lock_request_id' => (string) $lockRequest->id,
                'lot_status' => LotStatus::RESERVED->value,
                'lot_is_locked' => true,
            ], 'Yêu cầu đặt cọc đã được gửi thành công và lô đất đã được lock.', 201);
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
            $model->load(['lot.area', 'user']);
            return $this->success($model->toArray());
        });
    }

    public function adminApprove(string $id, \App\Modules\Auth\Models\User $user): ServiceReturn
    {
        return $this->execute(function () use ($id, $user) {
            $model = $this->repository->findById($id);
            $this->validate($model !== null, 'Yêu cầu đặt cọc không tồn tại.', 404);
            $this->validate($model->status === LotDepositRequestStatus::PENDING, 'Yêu cầu này không ở trạng thái chờ xác nhận.', 400);

            $model->load('lot.area');
            $lot = $model->lot;
            $this->validate($lot !== null, 'Lô đất không tồn tại.', 404);

            if ($user->role?->name !== 'super_admin') {
                $this->validate($lot->area && $lot->area->branch_id === $user->branch_id, 'Bạn không có quyền thực hiện chức năng này.', 403);
            }

            $this->validate(
                in_array($lot->status, [LotStatus::AVAILABLE, LotStatus::RESERVED], true),
                'Lô đất không còn khả dụng.',
                400
            );

            $this->repository->updateById($id, ['status' => LotDepositRequestStatus::APPROVED->value]);
            $this->lotRepository->updateById($lot->id, [
                'status' => LotStatus::RESERVED->value,
                'is_locked' => true,
            ]);
            
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

            $model->load('lot.area');
            $lot = $model->lot;
            $this->validate($lot !== null, 'Lô đất không tồn tại.', 404);

            // Kiểm tra phân quyền chi nhánh (A4)
            if ($user->role?->name !== 'super_admin') {
                $this->validate($lot->area && $lot->area->branch_id === $user->branch_id, 'Bạn không có quyền thực hiện chức năng này.', 403);
            }

            $this->repository->updateById($id, [
                'status' => LotDepositRequestStatus::REJECTED->value,
                'reject_reason' => $reason
            ]);

            $activeLockRequest = $this->lotLockRequestRepository->findActiveByLotId((string) $lot->id);
            if ($activeLockRequest !== null && (string) $activeLockRequest->user_id === (string) $model->user_id) {
                $this->lotLockRequestRepository->updateById((string) $activeLockRequest->id, [
                    'status' => LotLockRequestStatus::REJECTED->value,
                    'rejected_by' => $user->id,
                    'rejected_at' => now(),
                    'reject_reason' => $reason,
                ]);

                $this->lotRepository->updateById($lot->id, [
                    'status' => LotStatus::AVAILABLE->value,
                    'is_locked' => false,
                ]);
            }
            
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

            $model->load('lot.area', 'user.employeeProfile');
            $lot = $model->lot;
            $this->validate($lot !== null, 'Lô đất không tồn tại.', 404);

            // A4 - Người dùng không có quyền xác nhận giao dịch
            if ($user->role?->name !== 'super_admin') {
                $this->validate($lot->area && $lot->area->branch_id === $user->branch_id, 'Bạn không có quyền thực hiện chức năng này.', 403);
            }

            // Update status
            $this->repository->updateById($id, ['status' => LotDepositRequestStatus::COMPLETED->value]);
            $this->lotRepository->updateById($lot->id, [
                'status' => LotStatus::SOLD->value,
                'is_locked' => true,
            ]);

            // Add points to employee
            $this->authRepository->addRewardPoints($model->user_id, 10);

            // Save history
            $this->rewardPointHistoryRepository->create([
                'user_id' => $model->user_id,
                'points_changed' => 10,
                'reason' => 'Thưởng giao dịch thành công lô đất ' . $lot->code,
                'related_id' => $id,
            ]);

            // Fire Event
            event(new \App\Modules\Area\Events\TransactionCompleted($model));

            return $this->success(null, 'Xác nhận giao dịch thành công.');
        }, useTransaction: true);
    }
}
