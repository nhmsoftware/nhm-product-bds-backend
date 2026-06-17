<?php

namespace App\Modules\Recruitment\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Recruitment\Interfaces\RecruitmentPostRepositoryInterface;
use App\Modules\Recruitment\Models\RecruitmentPost;
use Illuminate\Pagination\LengthAwarePaginator;

class RecruitmentPostRepository extends BaseRepository implements RecruitmentPostRepositoryInterface
{
    public function getModel()
    {
        return RecruitmentPost::class;
    }

    /**
     * Lấy danh sách bài tuyển dụng có phân trang và lọc
     *
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getFiltered(array $filters): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', "%{$search}%")
                  ->orWhere('job_position', 'ilike', "%{$search}%")
                  ->orWhere('department', 'ilike', "%{$search}%")
                  ->orWhereHas('branch', function ($qb) use ($search) {
                      $qb->where('name', 'ilike', "%{$search}%");
                  });

            });
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }
}
