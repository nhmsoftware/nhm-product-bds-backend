<?php

declare(strict_types=1);

namespace App\Modules\Area\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Area\Interfaces\LotDepositRequestRepositoryInterface;
use App\Modules\Area\Models\LotDepositRequest;
use App\Modules\Area\Models\Enums\LotDepositRequestStatus;

final class LotDepositRequestRepository extends BaseRepository implements LotDepositRequestRepositoryInterface
{
    /**
     * Define the model class specific for this repository
     *
     * @return string
     */
    public function getModel(): string
    {
        return LotDepositRequest::class;
    }

    public function hasPendingDepositRequestForLot(string $lotId): bool
    {
        return $this->model
            ->where('lot_id', $lotId)
            ->where('status', LotDepositRequestStatus::PENDING->value)
            ->exists();
    }

    public function getAdminList(\App\Modules\Area\DTO\FilterLotDepositRequestDTO $dto): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $this->model->with(['lot.area.project', 'user']);

        if ($dto->status !== null) {
            $query->where('status', $dto->status);
        }

        if ($dto->employee_id !== null) {
            $query->where('user_id', $dto->employee_id);
        }

        if ($dto->project_id !== null || $dto->branch !== null) {
            $query->whereHas('lot.area.project', function ($q) use ($dto) {
                if ($dto->project_id !== null) {
                    $q->where('id', $dto->project_id);
                }
                if ($dto->branch !== null) {
                    $q->where('branch', $dto->branch);
                }
            });
        }

        if ($dto->search !== null) {
            $search = $dto->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($qu) use ($search) {
                    $qu->where('name', 'iLike', "%{$search}%")
                       ->orWhere('phone', 'iLike', "%{$search}%")
                       ->orWhere('email', 'iLike', "%{$search}%");
                })->orWhereHas('lot', function ($ql) use ($search) {
                    $ql->where('code', 'iLike', "%{$search}%");
                })->orWhereHas('lot.area.project', function ($qp) use ($search) {
                    $qp->where('name', 'iLike', "%{$search}%");
                });
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($dto->per_page, ['*'], 'page', $dto->page);
    }
}
