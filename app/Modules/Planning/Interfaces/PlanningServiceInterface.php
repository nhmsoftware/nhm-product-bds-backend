<?php

namespace App\Modules\Planning\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Planning\DTO\PlanningListDTO;

/**
 * Interface PlanningServiceInterface
 * 
 * @package App\Modules\Planning\Interfaces
 */
interface PlanningServiceInterface
{
    /**
     * Lấy danh sách quy hoạch công khai.
     *
     * @param PlanningListDTO $dto
     * @return ServiceReturn
     */
    public function getPublicList(PlanningListDTO $dto): ServiceReturn;

    /**
     * Tìm kiếm quy hoạch.
     *
     * @param string $keyword
     * @param int $perPage
     * @param int $page
     * @return ServiceReturn
     */
    public function search(string $keyword, int $perPage = 10, int $page = 1): ServiceReturn;

    /**
     * Lấy chi tiết quy hoạch.
     *
     * @param string $id
     * @return ServiceReturn
     */
    public function getDetail(string $id): ServiceReturn;

    /**
     * Lấy danh sách các tỉnh/thành phố dùng để lọc.
     *
     * @return ServiceReturn
     */
    public function getFilterCities(): ServiceReturn;

    /**
     * Lấy link tải hồ sơ PDF quy hoạch.
     *
     * @param string $id
     * @return ServiceReturn
     */
    public function getDownloadLink(string $id): ServiceReturn;
}
