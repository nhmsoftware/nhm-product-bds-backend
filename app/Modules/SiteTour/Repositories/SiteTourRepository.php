<?php

namespace App\Modules\SiteTour\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\SiteTour\Interfaces\SiteTourRepositoryInterface;
use App\Modules\SiteTour\Models\SiteTour;
use Illuminate\Database\Eloquent\Collection;

final class SiteTourRepository extends BaseRepository implements SiteTourRepositoryInterface
{
    /**
     * Xác định class Model đại diện cho Repository này.
     *
     * @return string Tên lớp Model dạng chuỗi
     */
    public function getModel(): string
    {
        return SiteTour::class;
    }

    /**
     * Tìm danh sách các hoạt động dẫn khách gần đây nhất của nhân viên.
     *
     * @param string $userId ID của nhân viên (UUID)
     * @param int $limit Số lượng bản ghi tối đa
     * @return Collection
     */
    public function getRecentToursByUserId(string $userId, int $limit = 5): Collection
    {
        return $this->model
            ->where('user_id', $userId)
            ->with('project')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Tìm danh sách lịch sử các hoạt động dẫn khách kèm bộ lọc của nhân viên.
     *
     * @param string $userId ID của nhân viên (UUID)
     * @param array $filters Bộ lọc tìm kiếm
     * @return Collection
     */
    public function getTourHistory(string $userId, array $filters = []): Collection
    {
        $query = $this->model
            ->where('user_id', $userId)
            ->with('project')
            ->orderBy('created_at', 'desc');

        if (!empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        if (!empty($filters['customer_name'])) {
            $query->where('customer_name', 'like', '%' . $filters['customer_name'] . '%');
        }

        return $query->get();
    }
}
