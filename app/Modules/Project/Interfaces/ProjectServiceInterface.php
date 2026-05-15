<?php

namespace App\Modules\Project\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Project\DTO\ProjectListDTO;

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
}
