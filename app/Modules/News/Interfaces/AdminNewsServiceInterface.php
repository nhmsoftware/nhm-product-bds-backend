<?php

declare(strict_types=1);

namespace App\Modules\News\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\News\DTO\AdminCreateNewsDTO;
use App\Modules\News\DTO\AdminListNewsDTO;
use App\Modules\News\DTO\AdminUpdateNewsDTO;

interface AdminNewsServiceInterface
{
    /**
     * Lấy danh sách tin tức
     */
    public function getList(AdminListNewsDTO $dto): ServiceReturn;

    /**
     * Xem chi tiết tin tức
     */
    public function getDetail(string $id): ServiceReturn;

    /**
     * Thêm mới tin tức
     */
    public function create(AdminCreateNewsDTO $dto): ServiceReturn;

    /**
     * Cập nhật tin tức
     */
    public function update(AdminUpdateNewsDTO $dto): ServiceReturn;

    /**
     * Xóa tin tức
     */
    public function delete(string $id): ServiceReturn;
}
