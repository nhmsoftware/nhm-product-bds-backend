<?php

namespace App\Modules\News\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\News\DTO\GetNewsListDTO;
use App\Modules\News\DTO\SearchNewsDTO;
use App\Modules\News\Events\NewsLiked;
use App\Modules\News\Events\NewsUnliked;
use App\Modules\News\Interfaces\NewsLikeRepositoryInterface;
use App\Modules\News\Interfaces\NewsRepositoryInterface;
use App\Modules\News\Interfaces\NewsServiceInterface;

final class NewsService extends BaseService implements NewsServiceInterface
{
    public function __construct(
        private readonly NewsRepositoryInterface $newsRepository,
        private readonly NewsLikeRepositoryInterface $newsLikeRepository
    ) {
    }

    /**
     * Lấy danh sách bài viết tin tức (UC-08).
     * 
     * @param GetNewsListDTO $dto
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function getList(GetNewsListDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $featured = $this->newsRepository->getFeaturedNews();
            $paginatedNews = $this->newsRepository->getPublishedNews($dto->toArray());

            $data = [
                'featured' => $featured,
                'list' => $paginatedNews->items(),
                'pagination' => [
                    'total' => $paginatedNews->total(),
                    'per_page' => $paginatedNews->perPage(),
                    'current_page' => $paginatedNews->currentPage(),
                    'last_page' => $paginatedNews->lastPage(),
                ],
                'categories' => $this->getStaticCategories(), // Or fetch from DB if needed
            ];

            $emptyMessage = $dto->category ? 'Hiện chưa có bài viết trong danh mục này.' : 'Hiện chưa có bài viết nào.';
            $this->validate(count($data['list']) > 0 || count($data['featured']) > 0, $emptyMessage, 200);

            return $this->success($data, 'Tải danh sách tin tức thành công.');
        }, useTransaction: false);
    }

    /**
     * Tìm kiếm bài viết theo từ khóa (UC-09).
     * 
     * @param SearchNewsDTO $dto
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function search(SearchNewsDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $paginatedNews = $this->newsRepository->search($dto->keyword, $dto->perPage);

            $data = [
                'list' => $paginatedNews->items(),
                'pagination' => [
                    'total' => $paginatedNews->total(),
                    'per_page' => $paginatedNews->perPage(),
                    'current_page' => $paginatedNews->currentPage(),
                    'last_page' => $paginatedNews->lastPage(),
                ],
            ];

            $this->validate(count($data['list']) > 0, 'Không tìm thấy bài viết phù hợp.', 200);

            return $this->success($data, 'Tìm kiếm tin tức thành công.');
        }, useTransaction: false);
    }

    /**
     * Lấy thông tin chi tiết bài viết (UC-11).
     * 
     * @param string $idOrSlug
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function getDetail(string $idOrSlug): ServiceReturn
    {
        return $this->execute(function () use ($idOrSlug) {
            // Check if it's UUID or Slug
            if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $idOrSlug)) {
                $news = $this->newsRepository->find($idOrSlug);
            } else {
                $news = $this->newsRepository->findBySlug($idOrSlug);
            }

            $this->validate($news !== null, 'Bài viết không tồn tại hoặc đã bị xóa.', 404);
            $this->validate($news->is_published === true, 'Bạn không có quyền truy cập bài viết này.', 403);

            $related = $this->newsRepository->getRelatedNews($news);

            $data = [
                'detail' => $news,
                'related' => $related,
            ];

            return $this->success($data, 'Tải chi tiết bài viết thành công.');
        }, useTransaction: false);
    }

    /**
     * Thực hiện thích hoặc bỏ thích bài viết (UC-12).
     * 
     * @param string $newsId
     * @param string $userId
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function like(string $newsId, string $userId): ServiceReturn
    {
        return $this->execute(function () use ($newsId, $userId) {
            $news = $this->newsRepository->find($newsId);
            $this->validate($news !== null, 'Bài viết không tồn tại.', 404);

            $like = $this->newsLikeRepository->findLike($newsId, $userId);

            if ($like) {
                $this->newsLikeRepository->deleteById($like->id);
                $news->decrement('likes_count');

                event(new NewsUnliked($news, $userId));

                return $this->success([
                    'liked' => false,
                    'likes_count' => $news->likes_count
                ], 'Đã bỏ thích bài viết.');
            }

            $this->newsLikeRepository->create([
                'news_id' => $newsId,
                'user_id' => $userId
            ]);
            $news->increment('likes_count');

            event(new NewsLiked($news, $userId));

            return $this->success([
                'liked' => true,
                'likes_count' => $news->likes_count
            ], 'Đã thích bài viết.');
        }, useTransaction: true);
    }

    /**
     * Lấy danh sách các danh mục tin tức tĩnh.
     * 
     * @return array
     */
    private function getStaticCategories(): array
    {
        return [
            ['id' => 'all', 'name' => 'Tất cả'],
            ['id' => 'market', 'name' => 'Tin thị trường'],
            ['id' => 'project', 'name' => 'Dự án'],
            ['id' => 'investment', 'name' => 'Đầu tư'],
            ['id' => 'legal', 'name' => 'Pháp lý'],
            ['id' => 'other', 'name' => 'Khác'],
        ];
    }
}
