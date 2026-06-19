<?php

declare(strict_types=1);

namespace App\Modules\Area\Services;

use App\Core\DTOs\FilterDTO;
use App\Core\Services\BaseService;
use App\Core\Services\ServiceException;
use App\Core\Services\ServiceReturn;
use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Modules\Area\Interfaces\AreaRepositoryInterface;
use App\Modules\Area\Interfaces\AreaServiceInterface;
use App\Modules\Area\Interfaces\LotRepositoryInterface;
use App\Modules\Area\Interfaces\AreaCommentRepositoryInterface;
use App\Modules\Area\Interfaces\LotLockRequestRepositoryInterface;
use App\Modules\Area\DTO\SearchInventoryDTO;
use App\Modules\Area\DTO\CreateAreaCommentDTO;
use App\Modules\Area\DTO\RequestLockLotDTO;
use App\Modules\Area\Models\Enums\LotLockRequestStatus;
use App\Modules\Area\Models\Enums\LotStatus;
use App\Modules\Area\Models\Lot;
use App\Modules\Area\Events\LotLocked;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
        private readonly AreaCommentRepositoryInterface $areaCommentRepository,
        private readonly LotLockRequestRepositoryInterface $lotLockRequestRepository,
        private readonly AuthRepositoryInterface $authRepository
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
            $user = $this->authRepository->find($userId);
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
     * @param int $perPage
     * @return ServiceReturn
     */
    public function getInventoryMap(string $userId, string $areaId, int $perPage = 10): ServiceReturn
    {
        return $this->execute(function () use ($userId, $areaId, $perPage) {
            // 1. Kiểm tra Preconditions: Tài khoản người dùng tồn tại
            $user = $this->authRepository->find($userId);
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

            // 7. Tải bình luận
            $commentsPaginator = $this->areaCommentRepository->getCommentsByAreaId($areaId, $perPage);
            $commentsPaginator->getCollection()->transform(function ($comment) {
                return [
                    'id' => (string) $comment->id,
                    'area_id' => (string) $comment->area_id,
                    'user_id' => (string) $comment->user_id,
                    'user_name' => $comment->user ? $comment->user->name : 'Nhân viên hệ thống',
                    'content' => $comment->content,
                    'created_at' => $comment->created_at ? $comment->created_at->toIso8601String() : null,
                ];
            });

            // 8. Kiểm tra A2: Không có dữ liệu sơ đồ bảng hàng
            $this->validate(
                !$lots->isEmpty(),
                'Chưa có dữ liệu bảng hàng.',
                404
            );

            // Trả về dữ liệu sơ đồ bảng hàng thành công
            return $this->success([
                'area_id' => $area->id,
                'area_name' => $area->name,
                'google_maps_url' => $area->google_maps_url,
                'sales_board_image' => $area->sales_board_image,
                'sales_board_iframe' => $area->sales_board_iframe,
                'planning_check_url' => $area->planning_check_url,
                'intro_article' => $this->areaIntroArticle($area),
                'info_tabs' => $this->areaInfoTabs($area),
                'summary' => [
                    'area_size' => $area->area_size,
                    'direction' => $area->direction,
                    'status' => $area->status,
                ],
                'lots' => $lots->toArray(),
                'comments' => [
                    'current_page' => $commentsPaginator->currentPage(),
                    'data' => $commentsPaginator->items(),
                    'first_page_url' => $commentsPaginator->url(1),
                    'from' => $commentsPaginator->firstItem(),
                    'last_page' => $commentsPaginator->lastPage(),
                    'last_page_url' => $commentsPaginator->url($commentsPaginator->lastPage()),
                    'next_page_url' => $commentsPaginator->nextPageUrl(),
                    'path' => $commentsPaginator->path(),
                    'per_page' => $commentsPaginator->perPage(),
                    'prev_page_url' => $commentsPaginator->previousPageUrl(),
                    'to' => $commentsPaginator->lastItem(),
                    'total' => $commentsPaginator->total(),
                ],
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
            $user = $this->authRepository->find($userId);
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
            $lot->load(['planning', 'area']);

            // 6. Trả về thông tin lô đất kèm tên khu đất
            $data = $lot->toArray();
            $data['area_name'] = $lot->area ? $lot->area->name : null;

            // Xử lý mảng ảnh cho Lot gallery
            $lotImages = $lot->images ?? [];
            if (empty($lotImages) && $lot->image_url) {
                $lotImages = [$lot->image_url];
            }
            $data['images'] = $lotImages;

            if ($lot->area) {
                $areaImages = $lot->area->sales_board_images ?? [];
                if (empty($areaImages) && $lot->area->sales_board_image) {
                    $areaImages = [$lot->area->sales_board_image];
                }
                $data['area'] = $lot->area->toArray();
                $data['area']['sales_board_images'] = $areaImages;
                $data['area']['google_maps_url'] = $lot->area->google_maps_url;
                $data['area']['location'] = $lot->area->location;
                $data['area']['project_name'] = $lot->area->name;
            }

            $activeLockRequest = $this->lotLockRequestRepository->findActiveByLotId($lotId);
            $activeDepositRequest = $lot->depositRequests()
                ->whereIn('status', [1, 2])
                ->latest('created_at')
                ->first();
            $isLockedByMe = $activeLockRequest !== null && (string) $activeLockRequest->user_id === (string) $userId;
            $isDepositedByMe = $activeDepositRequest !== null && (string) $activeDepositRequest->user_id === (string) $userId;
            $isLockedByOther = $activeLockRequest !== null && !$isLockedByMe;
            $isDepositedByOther = $activeDepositRequest !== null && !$isDepositedByMe;

            $data['planning'] = $lot->planning ? $lot->planning->toArray() : null;
            $data['active_lock_request'] = $activeLockRequest ? [
                'id' => (string) $activeLockRequest->id,
                'user_id' => (string) $activeLockRequest->user_id,
                'is_mine' => $isLockedByMe,
                'created_at' => $activeLockRequest->created_at?->toIso8601String(),
            ] : null;
            $data['active_deposit_request'] = $activeDepositRequest ? [
                'id' => (string) $activeDepositRequest->id,
                'user_id' => (string) $activeDepositRequest->user_id,
                'status' => $activeDepositRequest->status?->serialize(),
                'is_mine' => $isDepositedByMe,
                'created_at' => $activeDepositRequest->created_at?->toIso8601String(),
            ] : null;
            $data['is_locked_by_me'] = $isLockedByMe;
            $data['is_locked_by_other'] = $isLockedByOther;
            $data['is_deposit_by_me'] = $isDepositedByMe;
            $data['is_deposit_by_other'] = $isDepositedByOther;
            $data['can_lock'] = !$lot->is_locked && $lot->status === LotStatus::AVAILABLE && $activeLockRequest === null && $activeDepositRequest === null;
            $data['can_deposit'] = !$lot->is_locked && !$isLockedByOther && !$isDepositedByOther && $activeDepositRequest === null;

            return $this->success($data, 'Tải chi tiết lô đất thành công.');
        }, useTransaction: false, returnCatchCallback: function (\Throwable $e) {
            if ($e instanceof ServiceException) {
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
     * Thêm bình luận nội bộ mới cho khu đất.
     *
     * @param CreateAreaCommentDTO $dto
     * @return ServiceReturn
     */
    public function addAreaComment(CreateAreaCommentDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // 1. Kiểm tra Preconditions: Tài khoản người dùng tồn tại
            $user = $this->authRepository->find($dto->userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);

            // 2. Kiểm tra Preconditions: Tài khoản đang hoạt động
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 3. Kiểm tra Preconditions: Quyền xem bảng hàng
            $this->validate(
                in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::CEO, UserRole::DIRECTOR, UserRole::MANAGER, UserRole::EMPLOYEE], true),
                'Bạn không có quyền truy cập bảng hàng.',
                403
            );

            // 4. Kiểm tra Khu đất tồn tại
            $area = $this->areaRepository->findById($dto->areaId);
            $this->validate($area !== null, 'Khu đất không tồn tại.', 404);

            // 4.5. Kiểm tra khu đất có bị khoá không
            if ($area->is_locked) {
                $this->throw('Khu đất đã bị khóa. Không thể thực hiện thao tác trên bảng hàng.', 403);
            }

            // 5. Kiểm tra Quyền truy cập khu đất này
            $this->validate(
                in_array($user->role, [UserRole::DIRECTOR, UserRole::CEO, UserRole::SUPER_ADMIN], true) || $this->areaRepository->hasAssignment($dto->userId, $area->id),
                'Bạn không có quyền truy cập khu đất này.',
                403
            );

            // 6. Tạo bình luận mới
            $comment = $this->areaCommentRepository->create([
                'area_id' => $dto->areaId,
                'user_id' => $dto->userId,
                'content' => $dto->content,
            ]);

            $comment->load('user');

            $data = [
                'id' => (string) $comment->id,
                'area_id' => (string) $comment->area_id,
                'user_id' => (string) $comment->user_id,
                'user_name' => $comment->user ? $comment->user->name : 'Nhân viên hệ thống',
                'content' => $comment->content,
                'created_at' => $comment->created_at ? $comment->created_at->toIso8601String() : null,
            ];

            // 7. Fire Realtime Event
            event(new \App\Modules\Area\Events\AreaCommentAdded($comment));

            return $this->success($data, 'Thêm bình luận thành công.');
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            if ($e instanceof ServiceException) {
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
            $user = $this->authRepository->find($dto->userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);

            // 2. Kiểm tra Preconditions: Tài khoản đang hoạt động
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 3. Kiểm tra Preconditions: Quyền lock lô (chỉ cho phép AGENT hoặc ADMIN)
            $this->validate(
                in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::CEO, UserRole::DIRECTOR, UserRole::EMPLOYEE], true),
                'Bạn không có quyền thực hiện chức năng này.',
                403
            );

            // 4. Kiểm tra Lô đất tồn tại và khóa row để tránh race với luồng đặt cọc.
            $lot = Lot::query()
                ->where('id', $dto->lotId)
                ->lockForUpdate()
                ->first();
            $this->validate($lot !== null, 'Lô đất không tồn tại.', 404);
            $lot->load('area');

            // 4.5. Kiểm tra khu đất có bị khoá không
            if ($lot->area && $lot->area->is_locked) {
                $this->throw('Khu đất đã bị khóa. Không thể thực hiện thao tác trên bảng hàng.', 403);
            }

            // Kiểm tra lô đất có bị khóa không
            if ($lot->is_locked) {
                $this->throw('Lô đất đã bị khóa.', 403);
            }

            $activeLockRequest = $this->lotLockRequestRepository->findActiveByLotId($dto->lotId);
            if ($activeLockRequest !== null) {
                $message = (string) $activeLockRequest->user_id === (string) $dto->userId
                    ? 'Bạn đã lock lô đất này.'
                    : 'Lô đất đang được người khác lock.';
                $this->throw($message, 403);
            }

            $activeDepositRequest = $lot->depositRequests()
                ->whereIn('status', [1, 2])
                ->latest('created_at')
                ->first();
            if ($activeDepositRequest !== null) {
                $message = (string) $activeDepositRequest->user_id === (string) $dto->userId
                    ? 'Bạn đã gửi yêu cầu đặt cọc lô đất này.'
                    : 'Lô đất đang có yêu cầu đặt cọc của người khác.';
                $this->throw($message, 403);
            }

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

            $expiresAt = $this->lotLockExpiresAt();

            // 7. Tạo lock lô — tự động thành công, không cần Admin duyệt
            $lockRequest = $this->lotLockRequestRepository->create([
                'lot_id'     => $dto->lotId,
                'user_id'    => $dto->userId,
                'reason'     => $dto->reason,
                'status'     => LotLockRequestStatus::APPROVED->value,
                'approved_by' => $dto->userId,
                'approved_at' => now(),
                'expires_at' => $expiresAt,
            ]);

            // 8. Cập nhật trạng thái lô đất → Đang giữ chỗ
            $lot->update([
                'status'    => LotStatus::RESERVED->value,
                'is_locked' => true,
            ]);

            // 9. Fire Event
            event(new LotLocked($lot, $lockRequest));

            $data = [
                'id'          => (string) $lockRequest->id,
                'lot_id'      => (string) $lockRequest->lot_id,
                'user_id'     => (string) $lockRequest->user_id,
                'reason'      => $lockRequest->reason,
                'status'      => LotLockRequestStatus::APPROVED->value,
                'status_label' => LotLockRequestStatus::APPROVED->label(),
                'expires_at'  => $lockRequest->expires_at ? $lockRequest->expires_at->toIso8601String() : null,
                'created_at'  => $lockRequest->created_at ? $lockRequest->created_at->toIso8601String() : null,
                'lot' => [
                    'id'     => $lot->id,
                    'code'   => $lot->code,
                    'status' => LotStatus::RESERVED->serialize(),
                ]
            ];

            return $this->success($data, 'Lock lô đất thành công.');
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            if ($e instanceof ServiceException) {
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

    private function lotLockExpiresAt(): Carbon
    {
        $setting = DB::table('inventory_settings')->where('key', 'lot_lock_approval_timeout')->first();
        $value = $setting ? json_decode((string) $setting->value, true) : null;
        $amount = max(1, (int) ($value['amount'] ?? 24));
        $unit = (string) ($value['unit'] ?? 'hours');

        return match ($unit) {
            'days' => now()->addDays($amount),
            default => now()->addHours($amount),
        };
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
            $user = $this->authRepository->find($userId);
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
                    'sales_board_iframe' => $area->sales_board_iframe,
                    'planning_check_url' => $area->planning_check_url,
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
                    'lot_id' => $lot->id,
                    'area_id' => $lot->area_id,
                    'code' => $lot->code,
                    'name' => $lot->area ? $lot->area->name : '',
                    'sales_board_image' => $lot->area ? $lot->area->sales_board_image : null,
                    'sales_board_iframe' => $lot->area ? $lot->area->sales_board_iframe : null,
                    'planning_check_url' => $lot->area ? $lot->area->planning_check_url : null,
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
            if ($e instanceof ServiceException) {
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

    /**
     * Tạo Area và danh sách Lots. Trả về Area model.
     * (Hàm này dùng nội bộ hoặc trong CUD qua Service khác orchestration).
     *
     * @param \App\Modules\Area\DTO\CreateAreaDTO $areaDto
     * @param \App\Modules\Area\DTO\CreateLotDTO[] $lotDtos
     * @return \App\Modules\Area\Models\Area
     */
    public function createAreaWithLots(\App\Modules\Area\DTO\CreateAreaDTO $areaDto, array $lotDtos): \App\Modules\Area\Models\Area
    {
        // Tạo Area
        $area = $this->areaRepository->create($areaDto->toArray());

        // Tạo danh sách Lots liên kết với Area
        foreach ($lotDtos as $lotDto) {
            $lotData = $lotDto->toArray();
            $lotData['area_id'] = $area->id;
            $this->lotRepository->create($lotData);
        }

        return $area;
    }

    /**
     * [Admin] Khóa/Mở khóa lô đất.
     */
    public function lockUnlockLot(string $userId, string $id, bool $isLocked): ServiceReturn
    {
        return $this->execute(function () use ($userId, $id, $isLocked) {
            $user = $this->authRepository->find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin người dùng.', 404);

            $lot = $this->lotRepository->findById($id);
            $this->validate($lot !== null, 'Lô đất không tồn tại.', 404);

            $lot->load('area');

            // General Director chỉ được Lock/Unlock Lot chi nhánh của bản thân
            if ($user->role === UserRole::DIRECTOR && $lot->area) {
                $this->validate($lot->area->branch_id === $user->branch_id, 'Bạn không có quyền thực hiện chức năng này trên lô đất của chi nhánh khác.', 403);
            }

            // Kiểm tra trạng thái hiện tại
            if ($isLocked && $lot->is_locked) {
                $this->throw('Lô đất đã được khóa.', 400);
            }
            if (!$isLocked && !$lot->is_locked) {
                $this->throw('Lô đất đang hoạt động.', 400);
            }

            $updatedLot = $this->lotRepository->updateById($id, ['is_locked' => $isLocked]);

            $message = $isLocked ? 'Khóa lô đất thành công.' : 'Cập nhật trạng thái lô đất thành công.';

            return $this->success(
                $updatedLot,
                $message
            );
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            if ($e instanceof ServiceException) {
                return ServiceReturn::error($e->getMessage(), $e->getCode());
            }
            return ServiceReturn::error('Không thể cập nhật trạng thái lô đất.', 500);
        });
    }

    /**
     * Đồng bộ bảng hàng (Area & Lot) cho một dự án.
     */
    public function bulkSyncAreasWithLots(string $projectId, array $areasData): void
    {
        $keepAreaIds = [];

        foreach ($areasData as $areaData) {
            $areaId = $areaData['id'] ?? null;
            if ($areaId) {
                // Kiểm tra xem area có tồn tại và thuộc projectId không
                $area = $this->areaRepository->findByIdAndProjectId($areaId, $projectId);
                $this->validate($area !== null, "Khu đất không hợp lệ: $areaId", 400);

                $this->areaRepository->updateById($areaId, $areaData);
            } else {
                $areaData['project_id'] = $projectId;
                $area = $this->areaRepository->create($areaData);
                $areaId = $area->id;
            }

            $keepAreaIds[] = $areaId;
            $keepLotIds = [];

            $lotsData = $areaData['lots'] ?? [];
            foreach ($lotsData as $lotData) {
                $lotId = $lotData['id'] ?? null;
                if ($lotId) {
                    $lot = $this->lotRepository->findByIdAndAreaId($lotId, $areaId);
                    $this->validate($lot !== null, "Lô đất không hợp lệ: $lotId", 400);

                    $this->lotRepository->updateById($lotId, $lotData);
                } else {
                    $lotData['area_id'] = $areaId;
                    $lot = $this->lotRepository->create($lotData);
                    $lotId = $lot->id;
                }
                $keepLotIds[] = $lotId;
            }

            // Xóa các Lot không còn trong danh sách
            $lotsToDelete = $this->lotRepository->getLotsToDelete($areaId, $keepLotIds);

            foreach ($lotsToDelete as $lotToDelete) {
                if (in_array($lotToDelete->status, [LotStatus::SOLD, LotStatus::RESERVED], true)) {
                    $this->throw("Không thể xóa lô đất '{$lotToDelete->name}' vì đang ở trạng thái đã giao dịch/giữ chỗ.", 400);
                }
                $this->lotRepository->deleteById((string)$lotToDelete->id);
            }
        }

        // Xóa các Area không còn trong danh sách
        $areasToDelete = $this->areaRepository->getAreasToDelete($projectId, $keepAreaIds);

        foreach ($areasToDelete as $areaToDelete) {
            // Kiểm tra xem area này có lot nào SOLD hoặc RESERVED không
            $hasLockedLots = $this->lotRepository->hasLockedLots($areaToDelete->id);

            if ($hasLockedLots) {
                $this->throw("Không thể xóa phân khu '{$areaToDelete->name}' vì có chứa lô đất đã giao dịch/giữ chỗ.", 400);
            }

            $this->areaRepository->deleteById((string)$areaToDelete->id);
        }
    }

    private function areaIntroArticle($area): array
    {
        $areaName = $area->name;
        $remainingLots = (int) ($area->remaining_lots ?? 0);
        $totalLots = (int) ($area->total_lots ?? 0);

        return [
            'title' => "Giới thiệu {$areaName}",
            'summary' => "{$areaName} được tổ chức như một bảng hàng trực quan để đội kinh doanh theo dõi trạng thái từng lô, kiểm tra quy hoạch và phối hợp tư vấn khách hàng ngay trên mobile.",
            'body' => "Khu vực hiện có {$remainingLots}/{$totalLots} lô còn khả dụng. Dữ liệu vị trí, pháp lý, mặt bằng và tài liệu được chuẩn hóa theo từng phân khu để nhân sự có thể tra cứu nhanh trong quá trình gặp khách, đi site tour hoặc gửi thông tin cho quản lý.",
        ];
    }

    private function areaInfoTabs($area): array
    {
        $areaName = $area->name ?: 'khu đất';
        $location = $area->location ?: 'Đang cập nhật vị trí';
        $legalInfo = $area->legal_info;
        $legalItems = is_array($legalInfo) ? $legalInfo : (is_string($legalInfo) ? json_decode($legalInfo, true) : null);
        $legalText = is_array($legalItems) && count($legalItems) > 0
            ? implode(', ', array_values($legalItems))
            : 'Hồ sơ pháp lý đang được cập nhật theo từng lô.';
        $salesBoardImages = is_array($area->sales_board_images) ? $area->sales_board_images : [];

        return [
            [
                'key' => 'location',
                'label' => 'Vị trí',
                'title' => 'Vị trí khu đất',
                'content' => "{$area->name} nằm tại {$location}. Nhân sự có thể dùng nút chỉ đường hoặc bản đồ khu đất để định vị nhanh trước khi gặp khách.",
                'action_label' => $area->google_maps_url ? 'Mở chỉ đường' : null,
                'action_url' => $area->google_maps_url,
            ],
            [
                'key' => 'legal',
                'label' => 'Pháp lý',
                'title' => 'Thông tin pháp lý',
                'content' => $legalText,
                'action_label' => null,
                'action_url' => null,
            ],
            [
                'key' => 'floor_plan',
                'label' => 'Mặt bằng',
                'title' => 'Sơ đồ mặt bằng',
                'content' => "Bảng hàng hiển thị {$area->total_lots} lô, cập nhật trạng thái còn hàng, giữ chỗ, đã bán và không bán theo thời gian thực.",
                'image_url' => $area->sales_board_image ?: ($salesBoardImages[0] ?? null),
                'action_label' => $area->planning_check_url ? 'Kiểm tra quy hoạch' : null,
                'action_url' => $area->planning_check_url,
            ],
            [
                'key' => 'documents',
                'label' => 'Tài liệu',
                'title' => 'Tài liệu bán hàng',
                'content' => "Tài liệu nội bộ của {$areaName} gồm hình ảnh bảng hàng, hồ sơ quy hoạch, thông tin sản phẩm và các ghi chú tư vấn đã được chuẩn hóa cho đội kinh doanh.",
                'action_label' => $area->brochure ? 'Mở tài liệu' : null,
                'action_url' => $area->brochure,
            ],
        ];
    }
}
