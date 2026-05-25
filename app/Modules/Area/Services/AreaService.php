<?php

declare(strict_types=1);

namespace App\Modules\Area\Services;

use App\Core\DTOs\FilterDTO;
use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Auth\Models\User;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Modules\Area\Interfaces\AreaRepositoryInterface;
use App\Modules\Area\Interfaces\AreaServiceInterface;
use App\Modules\Area\Interfaces\LotRepositoryInterface;
use App\Modules\Area\Interfaces\LotCommentRepositoryInterface;
use App\Modules\Area\Interfaces\LotLockRequestRepositoryInterface;
use App\Modules\Area\DTO\SearchInventoryDTO;
use App\Modules\Area\DTO\CreateLotCommentDTO;
use App\Modules\Area\DTO\RequestLockLotDTO;
use App\Modules\Area\Models\Enums\LotStatus;
use App\Modules\Area\Events\LotLocked;

final class AreaService extends BaseService implements AreaServiceInterface
{
    /**
     * Khởi tạo Service và inject repositories.
     *
     * @param AreaRepositoryInterface $areaRepository
     * @param LotRepositoryInterface $lotRepository
     */
    public function __construct(
        private readonly AreaRepositoryInterface $areaRepository,
        private readonly LotRepositoryInterface $lotRepository,
        private readonly LotCommentRepositoryInterface $lotCommentRepository,
        private readonly LotLockRequestRepositoryInterface $lotLockRequestRepository
    ) {}

    /**
     * Tải danh sách khu đất/bảng hàng được phân quyền cho người dùng.
     *
     * @param string $userId
     * @param FilterDTO $filter
     * @return ServiceReturn
     */
    public function getAssignedLandAreas(string $userId, FilterDTO $filter): ServiceReturn
    {
        return $this->execute(function () use ($userId, $filter) {
            // 1. Kiểm tra Preconditions: Tài khoản người dùng tồn tại
            $user = User::find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);

            // 2. Kiểm tra Preconditions: Tài khoản đang hoạt động
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 3. Kiểm tra A1: Quyền truy cập bảng hàng (phải là ADMIN, AGENT hoặc BROKER)
            $this->validate(
                in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::CEO, UserRole::DIRECTOR, UserRole::MANAGER, UserRole::EMPLOYEE], true),
                'Bạn không có quyền xem bảng hàng.',
                403
            );

            // 4. Kiểm tra A3: Không có dữ liệu khu đất nào trong cơ sở dữ liệu
            $totalCount = $this->areaRepository->countAll();
            if ($totalCount === 0) {
                // Trả về danh sách trống kèm theo thông báo phù hợp
                $paginator = new \Illuminate\Pagination\LengthAwarePaginator([], 0, $filter->getPerPage(), $filter->getPage());
                return $this->success(
                    data: $paginator,
                    message: 'Chưa có dữ liệu khu đất.'
                );
            }

            // 5. Tải danh sách khu đất/bảng hàng theo phạm vi được cấp quyền
            $assignedAreas = $this->areaRepository->getAssignedAreas($userId, $filter);

            // 6. Kiểm tra A2: Chưa được phân quyền khu đất nào
            if ($assignedAreas->isEmpty()) {
                return $this->success(
                    data: $assignedAreas,
                    message: 'Chưa có khu đất nào được cấp quyền.'
                );
            }

            // Trả về danh sách khu đất thành công
            return $this->success(
                data: $assignedAreas,
                message: 'Tải danh sách khu đất thành công.'
            );
        }, useTransaction: false, returnCatchCallback: function (\Throwable $e) {
            // A4: Lỗi không tải được dữ liệu
            return ServiceReturn::error(
                message: 'Không thể tải danh sách khu đất. Vui lòng thử lại.',
                code: 500
            );
        });
    }

    /**
     * Xem sơ đồ bảng hàng của khu đất theo trạng thái từng lô.
     *
     * @param string $userId
     * @param string $areaId
     * @return ServiceReturn
     */
    public function getInventoryMap(string $userId, string $areaId): ServiceReturn
    {
        return $this->execute(function () use ($userId, $areaId) {
            // 1. Kiểm tra Preconditions: Tài khoản người dùng tồn tại
            $user = User::find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);

            // 2. Kiểm tra Preconditions: Tài khoản đang hoạt động
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 3. Kiểm tra Preconditions: Quyền xem bảng hàng
            $this->validate(
                in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::CEO, UserRole::DIRECTOR, UserRole::MANAGER, UserRole::EMPLOYEE], true),
                'Bạn không có quyền xem bảng hàng.',
                403
            );

            // 4. Kiểm tra khu đất có tồn tại
            $area = $this->areaRepository->findById($areaId);
            $this->validate($area !== null, 'Khu đất không tồn tại.', 404);

            // 5. Kiểm tra A1: Quyền truy cập khu đất (phải được cấp quyền hoặc là admin)
            $this->validate(
                in_array($user->role, [UserRole::DIRECTOR, UserRole::CEO, UserRole::SUPER_ADMIN], true) || $this->areaRepository->hasAssignment($userId, $areaId),
                'Bạn không có quyền truy cập khu đất này.',
                403
            );

            // 6. Tải sơ đồ/danh sách các lô đất
            $lots = $this->lotRepository->getLotsByAreaId($areaId);

            // 7. Kiểm tra A2: Không có dữ liệu sơ đồ bảng hàng
            $this->validate(
                !$lots->isEmpty(),
                'Chưa có dữ liệu bảng hàng.',
                404
            );

            // Trả về dữ liệu sơ đồ bảng hàng thành công
            return $this->success([
                'area_id' => $area->id,
                'area_name' => $area->name,
                'sales_board_image' => $area->sales_board_image,
                'lots' => $lots->toArray(),
            ], 'Tải sơ đồ bảng hàng thành công.');
        }, useTransaction: false, returnCatchCallback: function (\Throwable $e) {
            return ServiceReturn::error(
                message: 'Không thể tải sơ đồ bảng hàng. Vui lòng thử lại.',
                code: 500
            );
        });
    }

    /**
     * Xem thông tin chi tiết lô đất.
     *
     * @param string $userId
     * @param string $lotId
     * @return ServiceReturn
     */
    public function getLotDetail(string $userId, string $lotId): ServiceReturn
    {
        return $this->execute(function () use ($userId, $lotId) {
            // 1. Kiểm tra Preconditions: Tài khoản người dùng tồn tại
            $user = User::find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);

            // 2. Kiểm tra Preconditions: Tài khoản đang hoạt động
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 3. Kiểm tra Preconditions: Quyền xem bảng hàng
            $this->validate(
                in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::CEO, UserRole::DIRECTOR, UserRole::MANAGER, UserRole::EMPLOYEE], true),
                'Bạn không có quyền xem bảng hàng.',
                403
            );

            // 4. Kiểm tra A4: Lô đất không tồn tại
            $lot = $this->lotRepository->findLotWithArea($lotId);
            $this->validate($lot !== null, 'Lô đất không tồn tại.', 404);

            // 5. Kiểm tra A1: Quyền truy cập khu đất của lô đất này
            $this->validate(
                in_array($user->role, [UserRole::DIRECTOR, UserRole::CEO, UserRole::SUPER_ADMIN], true) || $this->areaRepository->hasAssignment($userId, $lot->area_id),
                'Bạn không có quyền truy cập khu đất này.',
                403
            );

            // Eager load the planning relation
            $lot->load(['planning']);

            // Get comments via LotCommentRepository
            $commentsRaw = $this->lotCommentRepository->getCommentsByLotId($lotId);
            $comments = $commentsRaw->map(function ($comment) {
                return [
                    'id' => (string) $comment->id,
                    'user_id' => (string) $comment->user_id,
                    'user_name' => $comment->user ? $comment->user->name : 'Nhân viên hệ thống',
                    'content' => $comment->content,
                    'created_at' => $comment->created_at ? $comment->created_at->toIso8601String() : null,
                ];
            });

            // 6. Trả về thông tin lô đất kèm tên khu đất
            $data = $lot->toArray();
            $data['area_name'] = $lot->area ? $lot->area->name : null;
            $data['planning'] = $lot->planning ? $lot->planning->toArray() : null;
            $data['comments'] = $comments->toArray();

            return $this->success($data, 'Tải chi tiết lô đất thành công.');
        }, useTransaction: false, returnCatchCallback: function (\Throwable $e) {
            if ($e instanceof \App\Core\Services\ServiceException) {
                return ServiceReturn::error(
                    message: $e->getMessage(),
                    code: $e->getCode()
                );
            }
            return ServiceReturn::error(
                message: 'Không thể tải chi tiết lô đất. Vui lòng thử lại.',
                code: 500
            );
        });
    }

    /**
     * Thêm bình luận nội bộ mới cho lô đất.
     *
     * @param CreateLotCommentDTO $dto
     * @return ServiceReturn
     */
    public function addLotComment(CreateLotCommentDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // 1. Kiểm tra Preconditions: Tài khoản người dùng tồn tại
            $user = User::find($dto->userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);

            // 2. Kiểm tra Preconditions: Tài khoản đang hoạt động
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 3. Kiểm tra Preconditions: Quyền xem bảng hàng
            $this->validate(
                in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::CEO, UserRole::DIRECTOR, UserRole::MANAGER, UserRole::EMPLOYEE], true),
                'Bạn không có quyền truy cập bảng hàng.',
                403
            );

            // 4. Kiểm tra Lô đất tồn tại
            $lot = $this->lotRepository->findById($dto->lotId);
            $this->validate($lot !== null, 'Lô đất không tồn tại.', 404);

            // 5. Kiểm tra Quyền truy cập khu đất của lô đất này
            $this->validate(
                in_array($user->role, [UserRole::DIRECTOR, UserRole::CEO, UserRole::SUPER_ADMIN], true) || $this->areaRepository->hasAssignment($dto->userId, $lot->area_id),
                'Bạn không có quyền truy cập khu đất này.',
                403
            );

            // 6. Tạo bình luận mới
            $comment = $this->lotCommentRepository->create([
                'lot_id' => $dto->lotId,
                'user_id' => $dto->userId,
                'content' => $dto->content,
            ]);

            $comment->load('user');

            $data = [
                'id' => (string) $comment->id,
                'lot_id' => (string) $comment->lot_id,
                'user_id' => (string) $comment->user_id,
                'user_name' => $comment->user ? $comment->user->name : 'Nhân viên hệ thống',
                'content' => $comment->content,
                'created_at' => $comment->created_at ? $comment->created_at->toIso8601String() : null,
            ];

            return $this->success($data, 'Thêm bình luận thành công.');
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            if ($e instanceof \App\Core\Services\ServiceException) {
                return ServiceReturn::error(
                    message: $e->getMessage(),
                    code: $e->getCode()
                );
            }
            return ServiceReturn::error(
                message: 'Không thể thêm bình luận. Vui lòng thử lại.',
                code: 500
            );
        });
    }

    /**
     * Yêu cầu giữ chỗ (lock) lô đất.
     *
     * @param RequestLockLotDTO $dto
     * @return ServiceReturn
     */
    public function requestLockLot(RequestLockLotDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // 1. Kiểm tra Preconditions: Tài khoản người dùng tồn tại
            $user = User::find($dto->userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);

            // 2. Kiểm tra Preconditions: Tài khoản đang hoạt động
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 3. Kiểm tra Preconditions: Quyền lock lô (chỉ cho phép AGENT hoặc ADMIN)
            $this->validate(
                in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::CEO, UserRole::DIRECTOR, UserRole::EMPLOYEE], true),
                'Bạn không có quyền thực hiện chức năng này.',
                403
            );

            // 4. Kiểm tra Lô đất tồn tại
            $lot = $this->lotRepository->findById($dto->lotId);
            $this->validate($lot !== null, 'Lô đất không tồn tại.', 404);

            // 5. Kiểm tra Quyền truy cập khu đất của lô đất này
            $this->validate(
                in_array($user->role, [UserRole::DIRECTOR, UserRole::CEO, UserRole::SUPER_ADMIN], true) || $this->areaRepository->hasAssignment($dto->userId, $lot->area_id),
                'Bạn không có quyền thực hiện chức năng này.',
                403
            );

            // 6. Kiểm tra trạng thái lô đất
            if ($lot->status === LotStatus::RESERVED) {
                $this->throw('Lô đất đang được giữ chỗ.', 400);
            }
            if ($lot->status === LotStatus::SOLD) {
                $this->throw('Lô đất đã được bán.', 400);
            }
            if ($lot->status !== LotStatus::AVAILABLE) {
                $this->throw('Lô đất không ở trạng thái còn hàng.', 400);
            }

            // 7. Tạo yêu cầu lock lô
            $lockRequest = $this->lotLockRequestRepository->create([
                'lot_id' => $dto->lotId,
                'user_id' => $dto->userId,
                'reason' => $dto->reason,
            ]);

            // 8. Cập nhật trạng thái lô đất: Đang giữ chỗ (RESERVED)
            $lot->update([
                'status' => LotStatus::RESERVED,
            ]);

            // 9. Fire Event
            event(new LotLocked($lot, $lockRequest));

            $data = [
                'id' => (string) $lockRequest->id,
                'lot_id' => (string) $lockRequest->lot_id,
                'user_id' => (string) $lockRequest->user_id,
                'reason' => $lockRequest->reason,
                'created_at' => $lockRequest->created_at ? $lockRequest->created_at->toIso8601String() : null,
                'lot' => [
                    'id' => $lot->id,
                    'code' => $lot->code,
                    'status' => LotStatus::RESERVED->serialize(),
                ]
            ];

            return $this->success($data, 'Yêu cầu lock lô thành công.');
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            if ($e instanceof \App\Core\Services\ServiceException) {
                return ServiceReturn::error(
                    message: $e->getMessage(),
                    code: $e->getCode()
                );
            }
            return ServiceReturn::error(
                message: 'Không thể tạo yêu cầu lock lô.',
                code: 500
            );
        });
    }

    /**
     * Tìm kiếm khu đất hoặc lô đất.
     *
     * @param string $userId
     * @param SearchInventoryDTO $dto
     * @return ServiceReturn
     */
    public function searchInventory(string $userId, SearchInventoryDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($userId, $dto) {
            // 1. Kiểm tra Preconditions: Tài khoản người dùng tồn tại
            $user = User::find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);

            // 2. Kiểm tra Preconditions: Tài khoản đang hoạt động
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 3. Kiểm tra A3: Quyền xem bảng hàng (phải là ADMIN, AGENT hoặc BROKER)
            $this->validate(
                in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::CEO, UserRole::DIRECTOR, UserRole::MANAGER, UserRole::EMPLOYEE], true),
                'Bạn không có quyền truy cập dữ liệu này.',
                403
            );

            $isAdmin = in_array($user->role, [UserRole::DIRECTOR, UserRole::CEO, UserRole::SUPER_ADMIN], true);
            $keyword = trim($dto->keyword);

            // Tìm kiếm khu đất và lô đất
            $areas = $this->areaRepository->searchAreas($userId, $keyword, $isAdmin);
            $lots = $this->lotRepository->searchLots($userId, $keyword, $isAdmin);

            $results = collect();

            // Map areas
            foreach ($areas as $area) {
                $results->push([
                    'type' => 'area',
                    'id' => $area->id,
                    'name' => $area->name,
                    'sales_board_image' => $area->sales_board_image,
                    'total_lots' => (int) $area->total_lots,
                    'remaining_lots' => (int) $area->remaining_lots,
                    'status' => $area->remaining_lots > 0 ? 'Đang mở bán' : 'Hết hàng',
                    'target_id' => $area->id,
                ]);
            }

            // Map lots
            foreach ($lots as $lot) {
                $results->push([
                    'type' => 'lot',
                    'id' => $lot->id,
                    'code' => $lot->code,
                    'name' => $lot->area ? $lot->area->name : '',
                    'sales_board_image' => $lot->area ? $lot->area->sales_board_image : null,
                    'total_lots' => $lot->area ? (int) $lot->area->total_lots : 0,
                    'remaining_lots' => $lot->area ? (int) $lot->area->remaining_lots : 0,
                    'status' => $lot->status instanceof LotStatus ? $lot->status->label() : LotStatus::deserialize($lot->status)->label(),
                    'target_id' => $lot->id,
                ]);
            }

            // 4. Kiểm tra A2: Không tìm thấy dữ liệu phù hợp
            $this->validate(
                $results->isNotEmpty(),
                'Không tìm thấy bảng hàng hoặc lô đất phù hợp.',
                404
            );

            return $this->success(
                data: $results->values()->toArray(),
                message: 'Tìm kiếm dữ liệu bảng hàng thành công.'
            );
        }, useTransaction: false, returnCatchCallback: function (\Throwable $e) {
            if ($e instanceof \App\Core\Services\ServiceException) {
                return ServiceReturn::error(
                    message: $e->getMessage(),
                    code: $e->getCode()
                );
            }
            return ServiceReturn::error(
                message: 'Không thể thực hiện tìm kiếm. Vui lòng thử lại.',
                code: 500
            );
        });
    }
}

