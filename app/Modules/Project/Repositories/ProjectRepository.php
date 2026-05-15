<?php

namespace App\Modules\Project\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Project\DTO\ProjectListDTO;
use App\Modules\Project\Interfaces\ProjectRepositoryInterface;
use App\Modules\Project\Models\Project;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class ProjectRepository
 * 
 * @package App\Modules\Project\Repositories
 */
final class ProjectRepository extends BaseRepository implements ProjectRepositoryInterface
{
    public function getModel()
    {
        return Project::class;
    }

    /**
     * Lấy danh sách dự án công khai.
     * 
     * @param ProjectListDTO $dto
     * @return LengthAwarePaginator
     */
    public function listPublic(ProjectListDTO $dto): LengthAwarePaginator
    {
        $query = $this->model->where('is_public', true);

        if ($dto->search) {
            $query->where('name', 'like', '%' . $dto->search . '%');
        }

        if ($dto->status) {
            $query->where('status', $dto->status);
        }

        if ($dto->type) {
            $query->where('type', $dto->type);
        }

        if ($dto->location) {
            $query->where('location', 'like', '%' . $dto->location . '%');
        }

        if ($dto->minPrice) {
            $query->where('price', '>=', $dto->minPrice);
        }

        if ($dto->maxPrice) {
            $query->where('price', '<=', $dto->maxPrice);
        }

        return $query->latest()->paginate($dto->perPage, ['*'], 'page', $dto->page);
    }

    /**
     * Tìm dự án công khai theo ID.
     * 
     * @param string $id
     * @return Project|null
     */
    public function findPublicById(string $id): ?Project
    {
        return $this->model->where('is_public', true)->find($id);
    }

    /**
     * Tìm kiếm dự án công khai theo từ khóa.
     * 
     * @param string $keyword
     * @param int $perPage
     * @param int $page
     * @return LengthAwarePaginator
     */
    public function searchPublic(string $keyword, int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        return $this->model->where('is_public', true)
            ->where(function ($query) use ($keyword) {
                $query->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('location', 'like', '%' . $keyword . '%')
                    ->orWhere('description', 'like', '%' . $keyword . '%')
                    ->orWhereJsonContains('keywords', $keyword);
            })
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
