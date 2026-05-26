<?php

namespace App\Modules\Dashboard\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Dashboard\Interfaces\SystemCommentRepositoryInterface;
use App\Modules\Dashboard\Models\SystemComment;
use Illuminate\Pagination\LengthAwarePaginator;

final class SystemCommentRepository extends BaseRepository implements SystemCommentRepositoryInterface
{
    public function getModel(): string
    {
        return SystemComment::class;
    }

    /**
     * Lấy danh sách bình luận (có filter)
     *
     * @param array $filters (keyword, type, project_id, area_id)
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getComments(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with(['user', 'project']);

        if (!empty($filters['type'])) {
            $query->where('source_type', $filters['type']);
        }

        if (!empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        // Dành cho General Director: Lọc theo branch/area_id nếu được truyền vào
        // (Nếu có cả project_id và area_id thì ưu tiên các rule nghiệp vụ, ở đây dùng where() linh hoạt)
        if (!empty($filters['area_id'])) {
            $query->where(function ($q) use ($filters) {
                // News_internal có field area_id là string hoặc null
                // Lô đất thì area_id là uuid
                $q->where('area_id', $filters['area_id'])
                  ->orWhere('department', 'like', '%' . $filters['area_id'] . '%'); // Nếu area/department chứa string
            });
        }

        if (!empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('content', 'like', '%' . $keyword . '%')
                  ->orWhereHas('user', function ($uq) use ($keyword) {
                      $uq->where('name', 'like', '%' . $keyword . '%');
                  });
            });
        }

        $query->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }
}
