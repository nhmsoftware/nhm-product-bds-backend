<?php

namespace App\Modules\Planning\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Planning\DTO\PlanningListDTO;
use App\Modules\Planning\Interfaces\PlanningRepositoryInterface;
use App\Modules\Planning\Models\Planning;
use App\Modules\Planning\Models\Enums\PlanningStatus;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class PlanningRepository
 * 
 * @package App\Modules\Planning\Repositories
 */
final class PlanningRepository extends BaseRepository implements PlanningRepositoryInterface
{
    /**
     * Define the model class specific for this repository
     * @return string
     */
    public function getModel()
    {
        return Planning::class;
    }

    /**
     * Lấy danh sách quy hoạch có phân trang và lọc.
     *
     * @param PlanningListDTO $dto
     * @return LengthAwarePaginator
     */
    public function getList(PlanningListDTO $dto): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->where('status', PlanningStatus::PUBLIC->value);

        if ($dto->search) {
            $query->where(function ($q) use ($dto) {
                $q->where('title', 'ilike', '%' . $dto->search . '%')
                    ->orWhere('district', 'ilike', '%' . $dto->search . '%')
                    ->orWhere('city', 'ilike', '%' . $dto->search . '%');
            });
        }

        if ($dto->city && $dto->city !== 'Tất cả khu vực') {
            $query->where('city', $dto->city);
        }

        return $query->orderBy('updated_year', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($dto->perPage, ['*'], 'page', $dto->page);
    }

    /**
     * Lấy danh sách các tỉnh/thành phố có quy hoạch.
     *
     * @return array
     */
    public function getAvailableCities(): array
    {
        return $this->model->newQuery()
            ->where('status', PlanningStatus::PUBLIC->value)
            ->distinct()
            ->pluck('city')
            ->filter()
            ->values()
            ->toArray();
    }
}
