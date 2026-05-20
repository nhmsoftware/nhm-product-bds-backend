<?php

namespace App\Modules\DepartmentTransfer\Repositories;

use App\Core\DTOs\FilterDTO;
use App\Core\Repository\BaseRepository;
use App\Modules\DepartmentTransfer\Interfaces\DepartmentTransferRequestRepositoryInterface;
use App\Modules\DepartmentTransfer\Models\DepartmentTransferRequest;
use Illuminate\Pagination\LengthAwarePaginator;

class DepartmentTransferRequestRepository extends BaseRepository implements DepartmentTransferRequestRepositoryInterface
{
    /**
     * Xác định class Model liên kết với Repository này.
     *
     * @return string
     */
    public function getModel(): string
    {
        return DepartmentTransferRequest::class;
    }

    /**
     * Kiểm tra xem nhân viên đã có yêu cầu chuyển phòng ban nào đang chờ xử lý (pending) hay không.
     *
     * @param string $userId
     * @return bool
     */
    public function hasPendingRequest(string $userId): bool
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->exists();
    }

    /**
     * Lấy danh sách yêu cầu chuyển phòng ban có phân trang và lọc (UC-050).
     *
     * @param FilterDTO $filter
     * @return LengthAwarePaginator
     */
    public function getTransferRequests(FilterDTO $filter): LengthAwarePaginator
    {
        $query = $this->query()->with('user');

        $filters = $filter->getFilters();

        // Lọc theo trạng thái xử lý
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Sắp xếp
        $sortBy = $filter->getSortBy() ?? 'created_at';
        $direction = $filter->getDirection();

        return $query->orderBy($sortBy, $direction)
            ->paginate($filter->getPerPage(), ['*'], 'page', $filter->getPage());
    }
}
