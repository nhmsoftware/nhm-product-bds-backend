<?php

namespace App\Modules\News\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\News\DTO\GetNewsListDTO;
use App\Modules\News\DTO\SearchNewsDTO;
use App\Modules\News\DTO\CreateCommentDTO;
use App\Modules\News\DTO\CreateInternalPostDTO;
use App\Modules\News\DTO\UpdateInternalPostDTO;
use App\Modules\News\DTO\DeleteInternalPostDTO;
use App\Modules\News\Events\NewsLiked;
use App\Modules\News\Events\NewsUnliked;
use App\Modules\News\Events\NewsCommentCreated;
use App\Modules\News\Events\InternalPostCreated;
use App\Modules\News\Interfaces\NewsLikeRepositoryInterface;
use App\Modules\News\Interfaces\NewsRepositoryInterface;
use App\Modules\News\Interfaces\NewsCommentRepositoryInterface;
use App\Modules\News\Interfaces\NewsServiceInterface;
use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Modules\Auth\Models\Enums\UserRole;

final class NewsService extends BaseService implements NewsServiceInterface
{
    public function __construct(
        private readonly NewsRepositoryInterface $newsRepository,
        private readonly NewsLikeRepositoryInterface $newsLikeRepository,
        private readonly NewsCommentRepositoryInterface $newsCommentRepository,
        private readonly AuthRepositoryInterface $authRepository
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

            $userId = auth('api')->id();
            $likedNewsIds = [];
            if ($userId) {
                $allIds = array_unique(array_merge(
                    collect($featured)->pluck('id')->toArray(),
                    collect($paginatedNews->items())->pluck('id')->toArray()
                ));
                if (!empty($allIds)) {
                    $likedNewsIds = $this->newsLikeRepository->getLikedNewsIds($userId, $allIds);
                }
            }

            foreach ($featured as $news) {
                $news->is_liked = in_array($news->id, $likedNewsIds, true);
            }
            foreach ($paginatedNews->items() as $news) {
                $news->is_liked = in_array($news->id, $likedNewsIds, true);
            }

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

    public function getLikedNewsList(string $userId, array $params): ServiceReturn
    {
        return $this->execute(function () use ($userId, $params) {
            $perPage = (int) ($params['per_page'] ?? 10);
            $paginatedNews = $this->newsRepository->getLikedNews($userId, $perPage);

            foreach ($paginatedNews->items() as $news) {
                $news->setAttribute('is_liked', true);
            }

            $data = [
                'list' => $paginatedNews->items(),
                'pagination' => [
                    'total' => $paginatedNews->total(),
                    'per_page' => $paginatedNews->perPage(),
                    'current_page' => $paginatedNews->currentPage(),
                    'last_page' => $paginatedNews->lastPage(),
                ]
            ];

            return $this->success($data, 'Tải danh sách bài viết đã thích thành công.');
        });
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

    /**
     * Lấy danh sách tin tức nội bộ cho bảng tin của nhân viên/quản lý (UC-060).
     * 
     * @param string $userId
     * @param array $params
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function getInternalNewsFeed(string $userId, array $params = []): ServiceReturn
    {
        return $this->execute(function () use ($userId, $params) {
            // 1. Kiểm tra Preconditions: Người dùng tồn tại trong hệ thống
            $user = $this->authRepository->find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);

            // 2. Kiểm tra Preconditions: Người dùng đang hoạt động
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 3. Phân trang
            $perPage = isset($params['per_page']) ? (int) $params['per_page'] : 10;

            // 4. Gọi Repository để tải tin tức nội bộ dựa trên quyền
            $paginated = $this->newsRepository->getInternalNewsFeed($user, $perPage);

            // 5. Chuẩn hóa dữ liệu theo đặc tả
            $paginated->through(function ($news) {
                return [
                    'id' => (string) $news->id,
                    'author_avatar' => $news->author ? $news->author->avatar : null,
                    'author_name' => $news->author ? $news->author->name : 'Hệ thống',
                    'department' => $news->department,
                    'area' => $news->area,
                    'title' => $news->title,
                    'summary' => $news->summary,
                    'content' => $news->content,
                    'thumbnail' => $news->thumbnail,
                    'category' => $news->category,
                    'likes_count' => (int) $news->likes_count,
                    'comments_count' => 0, // Mocked theo đặc tả yêu cầu
                    'published_at' => $news->published_at ? $news->published_at->toIso8601String() : ($news->created_at ? $news->created_at->toIso8601String() : null),
                ];
            });

            // 6. Kịch bản lỗi tải dữ liệu hoặc không có bài viết (A1)
            $message = 'Tải bảng tin nội bộ thành công.';
            if ($paginated->isEmpty()) {
                $message = 'Chưa có bài viết nội bộ.';
            }

            $data = [
                'list' => $paginated->items(),
                'pagination' => [
                    'total' => $paginated->total(),
                    'per_page' => $paginated->perPage(),
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                ],
            ];

            return $this->success($data, $message);
        }, useTransaction: false, returnCatchCallback: function (\Throwable $e) {
            // A2 – Lỗi tải bảng tin
            return ServiceReturn::error(
                message: 'Không thể tải bảng tin nội bộ.',
                code: 500
            );
        });
    }

    /**
     * Lấy chi tiết bài viết nội bộ theo ID và kiểm tra quyền truy cập của User.
     * 
     * @param string $newsId
     * @param string $userId
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function getInternalNewsDetail(string $newsId, string $userId): ServiceReturn
    {
        return $this->execute(function () use ($newsId, $userId) {
            // 1. Kiểm tra Preconditions: Người dùng tồn tại trong hệ thống và đang hoạt động
            $user = $this->authRepository->find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 2. Tìm bài viết nội bộ
            $news = $this->newsRepository->find($newsId);
            $this->validate($news !== null, 'Bài viết không tồn tại.', 404);
            $this->validate($news->is_published === true, 'Bài viết không tồn tại.', 404);

            if (in_array($user->role, [UserRole::EMPLOYEE, UserRole::MANAGER], true)) {
                $this->validate($news->department === $user->department, 'Bạn không có quyền truy cập bài viết này.', 403);
            } elseif ($user->role === UserRole::DIRECTOR) {
                $this->validate($news->area === $user->area, 'Bạn không có quyền truy cập bài viết này.', 403);
            } elseif (in_array($user->role, [UserRole::CEO, UserRole::SUPER_ADMIN], true)) {
                // Toàn quyền
            } else {
                $this->throw('Bạn không có quyền truy cập bài viết này.', 403);
            }

            // 4. Lấy danh sách bình luận
            $commentsRaw = $this->newsCommentRepository->getCommentsByNewsId($newsId);
            $comments = $commentsRaw->map(function ($comment) {
                return [
                    'id' => (string) $comment->id,
                    'user_id' => (string) $comment->user_id,
                    'user_name' => $comment->user ? $comment->user->name : 'Người dùng hệ thống',
                    'user_avatar' => $comment->user ? $comment->user->avatar : null,
                    'content' => $comment->content,
                    'created_at' => $comment->created_at ? $comment->created_at->toIso8601String() : null,
                ];
            });

            // 5. Kiểm tra xem người dùng hiện tại đã thích bài viết chưa
            $like = $this->newsLikeRepository->findLike($newsId, $userId);
            $isLiked = $like !== null;

            // 6. Xây dựng danh sách hình ảnh đính kèm (nếu có thumbnail thì coi là ảnh đính kèm)
            $attachments = [];
            if ($news->thumbnail) {
                $attachments[] = [
                    'type' => 'image',
                    'url' => $news->thumbnail,
                    'name' => basename($news->thumbnail)
                ];
            }

            // 7. Dữ liệu chi tiết bài viết
            $detail = [
                'id' => (string) $news->id,
                'title' => $news->title,
                'slug' => $news->slug,
                'summary' => $news->summary,
                'content' => $news->content,
                'thumbnail' => $news->thumbnail,
                'category' => $news->category,
                'department' => $news->department,
                'area' => $news->area,
                'likes_count' => (int) $news->likes_count,
                'comments_count' => $comments->count(),
                'is_liked' => $isLiked,
                'published_at' => $news->published_at ? $news->published_at->toIso8601String() : ($news->created_at ? $news->created_at->toIso8601String() : null),
                'author' => [
                    'name' => $news->author ? $news->author->name : 'Hệ thống',
                    'avatar' => $news->author ? $news->author->avatar : null,
                ],
                'attachments' => $attachments,
            ];

            return $this->success([
                'detail' => $detail,
                'comments' => $comments
            ], 'Tải chi tiết bài viết thành công.');
        }, useTransaction: false, returnCatchCallback: function (\Throwable $e) {
            if ($e instanceof \App\Core\Services\ServiceException) {
                return ServiceReturn::error($e->getMessage(), $e, null, $e->getCode());
            }
            return ServiceReturn::error('Không thể tải chi tiết bài viết.', $e, null, 500);
        });
    }

    /**
     * Tạo bình luận mới cho bài viết nội bộ.
     * 
     * @param CreateCommentDTO $dto
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function createComment(CreateCommentDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // 1. Kiểm tra Preconditions: Người dùng tồn tại trong hệ thống và đang hoạt động
            $user = $this->authRepository->find($dto->userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 2. Tìm bài viết nội bộ
            $news = $this->newsRepository->find($dto->newsId);
            $this->validate($news !== null, 'Bài viết không tồn tại.', 404);
            $this->validate($news->is_published === true, 'Bài viết không tồn tại.', 404);

            if (in_array($user->role, [UserRole::EMPLOYEE, UserRole::MANAGER], true)) {
                $this->validate($news->department === $user->department, 'Bạn không có quyền bình luận bài viết này.', 403);
            } elseif ($user->role === UserRole::DIRECTOR) {
                $this->validate($news->area === $user->area, 'Bạn không có quyền bình luận bài viết này.', 403);
            } elseif (in_array($user->role, [UserRole::CEO, UserRole::SUPER_ADMIN], true)) {
                // Toàn quyền
            } else {
                $this->throw('Bạn không có quyền bình luận bài viết này.', 403);
            }

            // 4. Lưu bình luận
            $comment = $this->newsCommentRepository->create([
                'news_id' => $dto->newsId,
                'user_id' => $dto->userId,
                'content' => $dto->content,
            ]);

            // 5. Phát Domain Event
            event(new NewsCommentCreated($comment));

            // Load user
            $comment->load('user');

            $data = [
                'id' => (string) $comment->id,
                'news_id' => (string) $comment->news_id,
                'user_id' => (string) $comment->user_id,
                'user_name' => $comment->user ? $comment->user->name : 'Người dùng hệ thống',
                'user_avatar' => $comment->user ? $comment->user->avatar : null,
                'content' => $comment->content,
                'created_at' => $comment->created_at ? $comment->created_at->toIso8601String() : null,
            ];

            return $this->success($data, 'Đã gửi bình luận thành công.');
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            if ($e instanceof \App\Core\Services\ServiceException) {
                return ServiceReturn::error($e->getMessage(), $e, null, $e->getCode());
            }
            return ServiceReturn::error('Không thể gửi bình luận.', $e, null, 500);
        });
    }

    /**
     * Tạo bài viết nội bộ trong phạm vi phòng ban hoặc khu vực quản lý.
     * 
     * @param CreateInternalPostDTO $dto
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function createInternalPost(CreateInternalPostDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // 1. Kiểm tra Preconditions: Người dùng tồn tại và đang hoạt động
            $user = $this->authRepository->find($dto->userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // Kiểm tra phân quyền: Employee, Manager, Director, CEO, Super Admin
            $this->validate(
                in_array($user->role, [UserRole::EMPLOYEE, UserRole::MANAGER, UserRole::DIRECTOR, UserRole::CEO, UserRole::SUPER_ADMIN], true),
                'Bạn không có quyền đăng bài viết nội bộ.',
                403
            );

            // 2. Nội dung bài viết không được trống (A1)
            $this->validate(
                !empty(trim($dto->content)),
                'Vui lòng nhập nội dung bài viết.',
                422
            );

            // 3. Tiêu đề: Nếu trống thì tự sinh từ nội dung
            $title = $dto->title;
            if (empty(trim($title))) {
                $title = Str::limit(strip_tags($dto->content), 60, '...');
            }

            // Tạo slug duy nhất
            $slug = Str::slug($title);
            if (empty($slug)) {
                $slug = 'post';
            }
            $slug = $slug . '-' . uniqid('', true);

            // 4. Xử lý tải hình ảnh thumbnail (nếu có)
            $thumbnail = null;
            if ($dto->thumbnailFile) {
                $path = $dto->thumbnailFile->store('news', 'public');
                // A3 – Lỗi tải file hoặc hình ảnh không hợp lệ
                $this->validate(
                    $path !== false && $path !== null,
                    'Không thể tải hình ảnh lên.',
                    500
                );
                $thumbnail = Storage::url($path);
            } elseif (!empty($dto->thumbnailUrl)) {
                $thumbnail = $dto->thumbnailUrl;
            }

            // 5. Xác định phạm vi hiển thị dựa trên vai trò
            $department = null;
            $area = null;

            if (in_array($user->role, [UserRole::DIRECTOR, UserRole::CEO, UserRole::SUPER_ADMIN], true)) {
                // Director, CEO, SuperAdmin: Xem bài viết của khu vực hoặc toàn bộ
                $area = $user->area;
                if ($user->role === UserRole::DIRECTOR) {
                    $this->validate(!empty($area), 'Khu vực quản lý của giám đốc không xác định.', 400);
                }
            } else {
                // Employee & Manager: Xem bài viết của phòng ban
                $department = $user->department;
                $area = $user->area;
                $this->validate(!empty($department), 'Phòng ban của nhân viên không xác định.', 400);
            }

            // 6. Thực thi lưu bài viết nội bộ
            $news = $this->newsRepository->create([
                'title' => $title,
                'slug' => $slug,
                'summary' => Str::limit(strip_tags($dto->content), 150, '...'),
                'content' => $dto->content,
                'thumbnail' => $thumbnail,
                'category' => 'internal',
                'department' => $department,
                'area' => $area,
                'author_id' => $user->id,
                'is_published' => true,
                'is_featured' => false,
                'likes_count' => 0,
                'published_at' => now(),
            ]);

            $this->validate(
                $news !== null,
                'Không thể đăng bài viết.',
                500
            );

            // 7. Phát Domain Event
            event(new InternalPostCreated($news));

            return $this->success($news, 'Đăng bài viết thành công.');
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            if ($e instanceof \App\Core\Services\ServiceException) {
                return ServiceReturn::error($e->getMessage(), $e, null, $e->getCode());
            }
            return ServiceReturn::error('Không thể đăng bài viết.', $e, null, 500);
        });
    }

    /**
     * Cập nhật bài viết nội bộ (UC-065).
     * 
     * @param UpdateInternalPostDTO $dto
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function updateInternalPost(UpdateInternalPostDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // 1. Kiểm tra Preconditions: Người dùng tồn tại và đang hoạt động
            $user = $this->authRepository->find($dto->userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 2. Tìm bài viết nội bộ
            $news = $this->newsRepository->find($dto->id);
            $this->validate($news !== null, 'Bài viết không tồn tại.', 404);
            $this->validate($news->category === 'internal', 'Bài viết không tồn tại.', 404);

            // 3. Kiểm tra A1: Người dùng không phải chủ sở hữu bài viết
            $this->validate($news->author_id === $user->id, 'Bạn không có quyền chỉnh sửa bài viết này.', 403);

            // 4. Kiểm tra A2: Nội dung bài viết trống
            $this->validate(!empty(trim($dto->content)), 'Vui lòng nhập nội dung bài viết.', 422);

            // 5. Cập nhật dữ liệu
            $dataToUpdate = [
                'content' => $dto->content,
                'summary' => Str::limit(strip_tags($dto->content), 150, '...'),
            ];

            if ($dto->title !== null) {
                $dataToUpdate['title'] = $dto->title;
            }

            // 6. Xử lý tải hình ảnh thumbnail mới (nếu có)
            if ($dto->thumbnailFile) {
                $path = $dto->thumbnailFile->store('news', 'public');
                $this->validate(
                    $path !== false && $path !== null,
                    'Không thể tải hình ảnh lên.',
                    500
                );
                $dataToUpdate['thumbnail'] = Storage::url($path);
            } elseif ($dto->thumbnailUrl !== null) {
                $dataToUpdate['thumbnail'] = $dto->thumbnailUrl;
            }

            // 7. Thực thi cập nhật bài viết
            $updated = $this->newsRepository->updateById($news->id, $dataToUpdate);
            $this->validate($updated !== false, 'Không thể cập nhật bài viết.', 500);

            // Tải lại model để trả về dữ liệu mới
            $news->refresh();

            return $this->success($news, 'Cập nhật bài viết thành công.');
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            if ($e instanceof \App\Core\Services\ServiceException) {
                return ServiceReturn::error($e->getMessage(), $e, null, $e->getCode());
            }
            return ServiceReturn::error('Không thể cập nhật bài viết.', $e, null, 500);
        });
    }

    /**
     * Thực hiện thích hoặc bỏ thích bài viết nội bộ (UC-063).
     * 
     * @param string $newsId
     * @param string $userId
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function likeInternalPost(string $newsId, string $userId): ServiceReturn
    {
        return $this->execute(function () use ($newsId, $userId) {
            // 1. Kiểm tra Preconditions: Người dùng tồn tại và đang hoạt động
            $user = $this->authRepository->find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 2. Tìm bài viết nội bộ
            $news = $this->newsRepository->find($newsId);
            $this->validate($news !== null && $news->is_published === true, 'Bài viết không tồn tại.', 404);

            // 3. Kiểm tra phân quyền truy cập bài viết nội bộ (A1)
            if (in_array($user->role, [UserRole::EMPLOYEE, UserRole::MANAGER], true)) {
                $this->validate($news->department === $user->department, 'Bạn không có quyền tương tác bài viết này.', 403);
            } elseif ($user->role === UserRole::DIRECTOR) {
                $this->validate($news->area === $user->area, 'Bạn không có quyền tương tác bài viết này.', 403);
            } elseif (in_array($user->role, [UserRole::CEO, UserRole::SUPER_ADMIN], true)) {
                // Toàn quyền
            } else {
                $this->throw('Bạn không có quyền tương tác bài viết này.', 403);
            }

            // 4. Kiểm tra xem người dùng đã thích bài viết chưa
            $like = $this->newsLikeRepository->findLike($newsId, $userId);

            if ($like) {
                // Đã thích -> Bỏ thích
                $this->newsLikeRepository->deleteById($like->id);
                $news->decrement('likes_count');

                event(new NewsUnliked($news, $userId));

                return $this->success([
                    'liked' => false,
                    'likes_count' => (int) $news->likes_count
                ], 'Đã bỏ thích bài viết.');
            }

            // Chưa thích -> Thích
            $this->newsLikeRepository->create([
                'news_id' => $newsId,
                'user_id' => $userId
            ]);
            $news->increment('likes_count');

            event(new NewsLiked($news, $userId));

            return $this->success([
                'liked' => true,
                'likes_count' => (int) $news->likes_count
            ], 'Đã thích bài viết.');
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            if ($e instanceof \App\Core\Services\ServiceException) {
                return ServiceReturn::error($e->getMessage(), $e, null, $e->getCode());
            }
            return ServiceReturn::error('Không thể cập nhật lượt thích. Vui lòng thử lại.', $e, null, 500);
        });
    }

    /**
     * Xóa bài viết nội bộ (UC-066).
     * 
     * @param DeleteInternalPostDTO $dto
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function deleteInternalPost(DeleteInternalPostDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // 1. Kiểm tra Preconditions: Người dùng tồn tại và đang hoạt động
            $user = $this->authRepository->find($dto->userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 2. Tìm bài viết nội bộ
            $news = $this->newsRepository->find($dto->id);
            // A3 - Bài viết không tồn tại
            $this->validate($news !== null, 'Bài viết không tồn tại.', 404);
            $this->validate($news->category === 'internal', 'Bài viết không tồn tại.', 404);

            // 3. Kiểm tra A1: Người dùng không phải chủ sở hữu bài viết
            $this->validate($news->author_id === $user->id, 'Bạn không có quyền xóa bài viết này.', 403);

            // 4. Thực hiện xóa bài viết
            $deleted = $this->newsRepository->deleteById($news->id);
            // A4 - Lỗi xóa bài viết
            $this->validate($deleted === true, 'Không thể xóa bài viết.', 500);

            return $this->success(null, 'Xóa bài viết thành công.');
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            if ($e instanceof \App\Core\Services\ServiceException) {
                return ServiceReturn::error($e->getMessage(), $e, null, $e->getCode());
            }
            return ServiceReturn::error('Không thể xóa bài viết.', $e, null, 500);
        });
    }
}
