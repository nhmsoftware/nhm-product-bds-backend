<?php

declare(strict_types=1);

namespace App\Modules\News\Services;

use App\Core\Interfaces\ServiceReturn;
use App\Core\Services\BaseService;
use App\Modules\News\Interfaces\AdminNewsServiceInterface;
use App\Modules\News\DTO\AdminListNewsDTO;
use App\Modules\News\DTO\AdminCreateNewsDTO;
use App\Modules\News\DTO\AdminUpdateNewsDTO;
use Illuminate\Support\Str;

use App\Modules\News\Interfaces\NewsRepositoryInterface;

final class AdminNewsService extends BaseService implements AdminNewsServiceInterface
{
    public function __construct(
        private readonly NewsRepositoryInterface $newsRepository
    ) {}

    public function getList(AdminListNewsDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $filters = [
                'search' => $dto->search,
                'isPublished' => $dto->isPublished,
                'type' => $dto->type,
            ];
            $list = $this->newsRepository->getAdminList($filters, $dto->perPage, $dto->page);

            return $this->success($list, 'Tải danh sách tin tức thành công.');
        }, useTransaction: false);
    }

    public function getDetail(string $id): ServiceReturn
    {
        return $this->execute(function () use ($id) {
            $news = $this->newsRepository->findById($id);
            if ($news) {
                $news->load('author:id,name,email');
            }
            $this->validate($news !== null, 'Bài viết không tồn tại.', 404);

            return $this->success($news, 'Lấy thông tin bài viết thành công.');
        }, useTransaction: false);
    }

    public function create(AdminCreateNewsDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // Check unique slug
            $slug = $dto->slug;
            if ($this->newsRepository->existsBySlug($slug)) {
                $slug = $slug . '-' . Str::random(5);
            }

            try {
                $news = $this->newsRepository->create([
                    'title' => $dto->title,
                    'slug' => $slug,
                    'summary' => $dto->summary,
                    'content' => $dto->content,
                    'thumbnail' => $dto->thumbnail,
                    'category' => $dto->category,
                    'department' => $dto->department,
                    'area' => $dto->area,
                    'author_id' => $dto->authorId,
                    'is_published' => $dto->isPublished,
                    'is_featured' => $dto->isFeatured,
                    'published_at' => $dto->isPublished ? now() : null,
                ]);
            } catch (\Exception $e) {
                $this->throw('Không thể tạo bài viết.', 500);
            }

            return $this->success($news, 'Tạo bài viết thành công.');
        });
    }

    public function update(AdminUpdateNewsDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $news = $this->newsRepository->findById($dto->id);
            $this->validate($news !== null, 'Bài viết không tồn tại.', 404);

            $updateData = [];

            if ($dto->title !== null) {
                $updateData['title'] = $dto->title;
                $updateData['slug'] = $this->cloneSlugIfNeeded($dto->slug, $news->id);
            }
            if ($dto->summary !== null) $updateData['summary'] = $dto->summary;
            if ($dto->content !== null) $updateData['content'] = $dto->content;
            if ($dto->thumbnail !== null) $updateData['thumbnail'] = $dto->thumbnail;
            if ($dto->category !== null) $updateData['category'] = $dto->category;

            if ($dto->type !== null) {
                $updateData['department'] = $dto->department;
                $updateData['area'] = $dto->area;
            } else {
                if ($dto->department !== null) $updateData['department'] = $dto->department === '' ? null : $dto->department;
                if ($dto->area !== null) $updateData['area'] = $dto->area === '' ? null : $dto->area;
            }

            if ($dto->isPublished !== null) {
                $updateData['is_published'] = $dto->isPublished;
                if ($dto->isPublished && !$news->is_published) {
                    $updateData['published_at'] = now();
                } elseif (!$dto->isPublished) {
                    $updateData['published_at'] = null;
                }
            }

            if ($dto->isFeatured !== null) {
                $updateData['is_featured'] = $dto->isFeatured;
            }

            if (!empty($updateData)) {
                try {
                    $news = $this->newsRepository->updateById($news->id, $updateData);
                } catch (\Exception $e) {
                    $this->throw('Không thể cập nhật bài viết.', 500);
                }
            }

            return $this->success($news->fresh(), 'Cập nhật bài viết thành công.');
        });
    }

    public function delete(string $id): ServiceReturn
    {
        return $this->execute(function () use ($id) {
            $news = $this->newsRepository->findById($id);
            $this->validate($news !== null, 'Bài viết không tồn tại.', 404);

            try {
                $this->newsRepository->deleteById($id);
            } catch (\Exception $e) {
                $this->throw('Không thể ẩn bài viết.', 500);
            }

            return $this->success(null, 'Ẩn bài viết thành công.');
        });
    }

    private function cloneSlugIfNeeded(?string $slug, string $ignoreId): ?string {
        if (!$slug) return null;
        if ($this->newsRepository->existsBySlug($slug, $ignoreId)) {
            return $slug . '-' . Str::random(5);
        }
        return $slug;
    }
}
