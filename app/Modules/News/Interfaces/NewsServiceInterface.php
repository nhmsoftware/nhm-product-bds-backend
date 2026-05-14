<?php

namespace App\Modules\News\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\News\DTO\GetNewsListDTO;
use App\Modules\News\DTO\SearchNewsDTO;

interface NewsServiceInterface
{
    /**
     * Lấy danh sách tin tức.
     * 
     * @param GetNewsListDTO $dto
     * @return ServiceReturn
     */
    public function getList(GetNewsListDTO $dto): ServiceReturn;

    /**
     * Tìm kiếm tin tức.
     * 
     * @param SearchNewsDTO $dto
     * @return ServiceReturn
     */
    public function search(SearchNewsDTO $dto): ServiceReturn;

    /**
     * Lấy chi tiết tin tức.
     * 
     * @param string $idOrSlug
     * @return ServiceReturn
     */
    public function getDetail(string $idOrSlug): ServiceReturn;

    /**
     * Thích bài viết tin tức.
     * 
     * @param string $newsId
     * @param string $userId
     * @return ServiceReturn
     */
    public function like(string $newsId, string $userId): ServiceReturn;
}
