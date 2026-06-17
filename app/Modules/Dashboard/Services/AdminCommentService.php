<?php

namespace App\Modules\Dashboard\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Modules\Dashboard\DTO\GetCommentsDTO;
use App\Modules\Dashboard\Interfaces\AdminCommentServiceInterface;
use App\Modules\Dashboard\Interfaces\SystemCommentRepositoryInterface;
use App\Modules\Area\Interfaces\AreaCommentRepositoryInterface;
use App\Modules\News\Interfaces\NewsCommentRepositoryInterface;

final class AdminCommentService extends BaseService implements AdminCommentServiceInterface
{
    public function __construct(
        private readonly AuthRepositoryInterface $authRepository,
        private readonly SystemCommentRepositoryInterface $systemCommentRepository,
        private readonly AreaCommentRepositoryInterface $areaCommentRepository,
        private readonly NewsCommentRepositoryInterface $newsCommentRepository
    ) {}

    public function getList(string $userId, GetCommentsDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($userId, $dto) {
            $user = $this->authRepository->findById($userId);
            $this->validate($user, 'Người dùng không tồn tại.', 404);
            
            // Phân quyền
            $allowedRoles = [UserRole::SUPER_ADMIN, UserRole::CEO, UserRole::DIRECTOR];
            $this->validate(in_array($user->role, $allowedRoles, true), 'Bạn không có quyền truy cập chức năng này.', 403);

            $filters = $dto->toArray();
            
            // Giám đốc chi nhánh chỉ xem được comment thuộc khu vực của họ
            if ($user->role === UserRole::DIRECTOR) {
                if ($user->branch_id) {
                    $filters['area_id'] = $user->branch_id;
                } else {
                    $filters['area_id'] = 'NO_AREA'; // Prevent seeing all if no area assigned
                }
            }

            if (!empty($filters['area_id'])) {
                $areaId = $filters['area_id'];
                if ($areaId && !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $areaId)) {
                    $resolved = \Illuminate\Support\Facades\DB::table('branches')->where('name', $areaId)->value('id');
                    if ($resolved) {
                        $filters['area_id'] = $resolved;
                    }
                }
            }

            $paginator = $this->systemCommentRepository->getComments($filters, $dto->per_page);

            // Format data
            $paginator->getCollection()->transform(function ($item) {
                return [
                    'id' => $item->id,
                    'source_type' => $item->source_type,
                    'source_id' => $item->source_id,
                    'content' => $item->content,
                    'project' => $item->project ? [
                        'id' => $item->project->id,
                        'name' => $item->project->name,
                    ] : null,
                    'department' => $item->department,
                    'area_id' => $item->area_id,
                    'user' => $item->user ? [
                        'id' => $item->user->id,
                        'name' => $item->user->name,
                        'avatar' => $item->user->avatar,
                    ] : null,
                    'created_at' => $item->created_at ? $item->created_at->toIso8601String() : null,
                ];
            });

            return $this->success([
                'data' => $paginator->items(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ], 'Tải danh sách bình luận thành công.');
        }, useTransaction: false);
    }

    public function deleteComment(string $userId, string $commentId, string $sourceType): ServiceReturn
    {
        return $this->execute(function () use ($userId, $commentId, $sourceType) {
            $user = $this->authRepository->findById($userId);
            $this->validate($user, 'Người dùng không tồn tại.', 404);
            
            $allowedRoles = [UserRole::SUPER_ADMIN, UserRole::CEO, UserRole::DIRECTOR];
            $this->validate(in_array($user->role, $allowedRoles, true), 'Bạn không có quyền truy cập chức năng này.', 403);

            // Delete based on source type
            if ($sourceType === 'area_internal') {
                $comment = $this->areaCommentRepository->findById($commentId);
                $this->validate($comment, 'Bình luận không tồn tại.', 404);
                $this->areaCommentRepository->deleteById($commentId);
            } elseif (in_array($sourceType, ['news_public', 'news_internal'], true)) {
                $comment = $this->newsCommentRepository->findById($commentId);
                $this->validate($comment, 'Bình luận không tồn tại.', 404);
                $this->newsCommentRepository->deleteById($commentId);
            } else {
                $this->throw('Loại bình luận không hợp lệ.', 400);
            }

            return $this->success(null, 'Xóa bình luận thành công.');
        }, useTransaction: true);
    }
}
