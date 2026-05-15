<?php

namespace App\Modules\Project\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Project\DTO\ProjectListDTO;
use App\Modules\Project\Models\Project;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface ProjectRepositoryInterface
 * 
 * @package App\Modules\Project\Interfaces
 */
interface ProjectRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Lấy danh sách dự án công khai.
     * 
     * @param ProjectListDTO $dto
     * @return LengthAwarePaginator
     */
    public function listPublic(ProjectListDTO $dto): LengthAwarePaginator;

    /**
     * Tìm dự án công khai theo ID.
     * 
     * @param string $id
     * @return Project|null
     */
    public function findPublicById(string $id): ?Project;

    /**
     * Tìm kiếm dự án công khai theo từ khóa.
     * 
     * @param string $keyword
     * @param int $perPage
     * @param int $page
     * @return LengthAwarePaginator
     */
    public function searchPublic(string $keyword, int $perPage = 10, int $page = 1): LengthAwarePaginator;
}
