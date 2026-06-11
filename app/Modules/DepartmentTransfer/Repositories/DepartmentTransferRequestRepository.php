<?php

namespace App\Modules\DepartmentTransfer\Repositories;

use App\Core\DTOs\FilterDTO;
use App\Core\Repository\BaseRepository;
use App\Modules\DepartmentTransfer\Interfaces\DepartmentTransferRequestRepositoryInterface;
use App\Modules\DepartmentTransfer\Models\DepartmentTransferRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Modules\DepartmentTransfer\Models\Enums\RequestStatus;

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
            ->where('status', RequestStatus::PENDING->value)
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
        $this->applyStatusFilter($query, $filter);

        // Sắp xếp
        $sortBy = $filter->getSortBy() ?? 'created_at';
        $direction = $filter->getDirection();

        return $query->orderBy($sortBy, $direction)
            ->paginate($filter->getPerPage(), ['*'], 'page', $filter->getPage());
    }

    /**
     * Lấy lịch sử yêu cầu chuyển phòng ban của một nhân viên.
     *
     * @param string $userId
     * @param FilterDTO $filter
     * @return LengthAwarePaginator
     */
    public function getUserTransferRequests(string $userId, FilterDTO $filter): LengthAwarePaginator
    {
        $query = $this->query()
            ->with('user')
            ->where('user_id', $userId);

        $this->applyStatusFilter($query, $filter);

        $sortBy = $filter->getSortBy() ?? 'created_at';
        $direction = $filter->getDirection();

        return $query->orderBy($sortBy, $direction)
            ->paginate($filter->getPerPage(), ['*'], 'page', $filter->getPage());
    }

    private function applyStatusFilter($query, FilterDTO $filter): void
    {
        $filters = $filter->getFilters();

        if (empty($filters['status'])) {
            return;
        }

        try {
            $enum = RequestStatus::deserialize($filters['status']);
            $query->where('status', $enum->value);
        } catch (\InvalidArgumentException $e) {
            $query->whereRaw('1 = 0');
        }
    }
}
