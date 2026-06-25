<?php

namespace App\Modules\Project\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Project\DTO\ProjectListDTO;
use App\Modules\Project\DTO\CreateProjectDTO;
use App\Modules\Project\DTO\UpdateProjectDTO;
use App\Modules\Project\DTO\ListAdminProjectDTO;
use App\Modules\Project\DTO\BulkCreateProjectDTO;

/**
 * Interface ProjectServiceInterface
 * 
 * @package App\Modules\Project\Interfaces
 */
interface ProjectServiceInterface
{
    /**
     * Lấy danh sách dự án công khai.
     * 
     * @param ProjectListDTO $dto
     * @return ServiceReturn
     */
    public function getPublicList(ProjectListDTO $dto): ServiceReturn;

    /**
     * Lấy chi tiết dự án công khai.
     * 
     * @param string $id
     * @return ServiceReturn
     */
    public function getPublicDetail(string $id): ServiceReturn;

    /**
     * Tìm kiếm dự án công khai.
     * 
     * @param string $keyword
     * @param int $perPage
     * @param int $page
     * @return ServiceReturn
     */
    public function searchProjects(string $keyword, int $perPage = 10, int $page = 1): ServiceReturn;

    /**
     * Lấy thông tin brochure của dự án.
     * 
     * @param string $id
     * @return ServiceReturn
     */
    public function getBrochure(string $id): ServiceReturn;

    /**
     * Lấy số hotline tư vấn của dự án.
     * 
     * @param string $id
     * @return ServiceReturn
     */
    public function getHotline(string $id): ServiceReturn;

    /**
     * Lấy danh sách tất cả loại hình khu đất.
     * 
     * @return ServiceReturn
     */
    public function getPublicTypes(): ServiceReturn;

    /**
     * [Admin] Lấy danh sách dự án.
     *
     * @param string $userId
     * @param ListAdminProjectDTO $dto
     * @return ServiceReturn
     */
    public function getAdminProjects(string $userId, ListAdminProjectDTO $dto): ServiceReturn;

    /**
     * [Admin] Lấy chi tiết dự án (kèm sơ đồ bảng hàng).
     *
     * @param string $userId
     * @param string $id
     * @return ServiceReturn
     */
    public function getProjectDetailAdmin(string $userId, string $id): ServiceReturn;

    /**
     * [Admin] Tạo dự án mới.
     *
     * @param string $userId
     * @param CreateProjectDTO $dto
     * @return ServiceReturn
     */
    public function createProject(string $userId, CreateProjectDTO $dto): ServiceReturn;

    /**
     * [Admin] Cập nhật dự án.
     *
     * @param string $userId
     * @param string $id
     * @param UpdateProjectDTO $dto
     * @return ServiceReturn
     */
    public function updateProject(string $userId, string $id, UpdateProjectDTO $dto): ServiceReturn;

    /**
     * [Admin] Khóa/Mở khóa dự án.
     *
     * @param string $userId
     * @param string $id
     * @param bool $isLocked
     * @return ServiceReturn
     */
    public function lockUnlockProject(string $userId, string $id, bool $isLocked): ServiceReturn;

    /**
     * [Admin] Tạo dự án (bulk) bao gồm thông tin dự án, bảng hàng, và các lô đất.
     *
     * @param string $userId
     * @param BulkCreateProjectDTO $dto
     * @return ServiceReturn
     */
    public function bulkCreateProject(string $userId, BulkCreateProjectDTO $dto): ServiceReturn;
}
