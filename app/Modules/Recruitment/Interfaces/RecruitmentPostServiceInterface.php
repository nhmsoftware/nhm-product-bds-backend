<?php

namespace App\Modules\Recruitment\Interfaces;

use App\Core\Services\ServiceReturn;

interface RecruitmentPostServiceInterface
{
    /**
     * Lấy danh sách bài tuyển dụng (UC-126)
     */
    public function getList(array $filters): ServiceReturn;

    /**
     * Xem chi tiết bài tuyển dụng (UC-126)
     */
    public function getDetail(string $id): ServiceReturn;

    /**
     * Tạo bài tuyển dụng mới (UC-126)
     */
    public function create(array $data): ServiceReturn;

    /**
     * Cập nhật bài tuyển dụng (UC-126)
     */
    public function update(string $id, array $data): ServiceReturn;

    /**
     * Xóa bài tuyển dụng (UC-126)
     */
    public function delete(string $id): ServiceReturn;
}
