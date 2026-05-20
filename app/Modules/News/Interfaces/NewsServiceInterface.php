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

    /**
     * Lấy danh sách tin tức nội bộ cho bảng tin của nhân viên/quản lý.
     * 
     * @param string $userId
     * @param array $params
     * @return ServiceReturn
     */
    public function getInternalNewsFeed(string $userId, array $params = []): ServiceReturn;

    /**
     * Lấy chi tiết bài viết nội bộ theo ID và kiểm tra quyền truy cập của User.
     * 
     * @param string $newsId
     * @param string $userId
     * @return ServiceReturn
     */
    public function getInternalNewsDetail(string $newsId, string $userId): ServiceReturn;

    /**
     * Tạo bình luận mới cho bài viết nội bộ.
     * 
     * @param \App\Modules\News\DTO\CreateCommentDTO $dto
     * @return ServiceReturn
     */
    public function createComment(\App\Modules\News\DTO\CreateCommentDTO $dto): ServiceReturn;

    /**
     * Tạo bài viết nội bộ trong phạm vi phòng ban hoặc khu vực quản lý.
     * 
     * @param \App\Modules\News\DTO\CreateInternalPostDTO $dto
     * @return ServiceReturn
     */
    public function createInternalPost(\App\Modules\News\DTO\CreateInternalPostDTO $dto): ServiceReturn;

    /**
     * Cập nhật bài viết nội bộ.
     * 
     * @param \App\Modules\News\DTO\UpdateInternalPostDTO $dto
     * @return ServiceReturn
     */
    public function updateInternalPost(\App\Modules\News\DTO\UpdateInternalPostDTO $dto): ServiceReturn;

    /**
     * Thích hoặc bỏ thích bài viết nội bộ.
     * 
     * @param string $newsId
     * @param string $userId
     * @return ServiceReturn
     */
    public function likeInternalPost(string $newsId, string $userId): ServiceReturn;
}
