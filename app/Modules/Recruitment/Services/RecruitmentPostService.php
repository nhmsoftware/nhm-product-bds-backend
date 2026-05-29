<?php

namespace App\Modules\Recruitment\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Recruitment\Interfaces\RecruitmentPostRepositoryInterface;
use App\Modules\Recruitment\Interfaces\RecruitmentPostServiceInterface;

class RecruitmentPostService extends BaseService implements RecruitmentPostServiceInterface
{
    public function __construct(
        private readonly RecruitmentPostRepositoryInterface $repository
    ) {
    }

    /**
     * Lấy danh sách bài tuyển dụng (UC-126)
     */
    public function getList(array $filters): ServiceReturn
    {
        return $this->execute(function () use ($filters) {
            $paginator = $this->repository->getFiltered($filters);
            
            if ($paginator->isEmpty()) {
                if (isset($filters['search']) || isset($filters['status'])) {
                    return $this->success($paginator, 'Không tìm thấy bài tuyển dụng phù hợp.');
                }
                return $this->success($paginator, 'Chưa có bài tuyển dụng nào.');
            }

            return $this->success($paginator, 'Lấy danh sách bài tuyển dụng thành công.');
        });
    }

    /**
     * Xem chi tiết bài tuyển dụng (UC-126)
     */
    public function getDetail(string $id): ServiceReturn
    {
        return $this->execute(function () use ($id) {
            $post = $this->repository->findById($id);
            
            $this->validate($post !== null, 'Không tìm thấy bài tuyển dụng phù hợp.', 404);

            return $this->success($post, 'Lấy chi tiết bài tuyển dụng thành công.');
        });
    }

    /**
     * Tạo bài tuyển dụng mới (UC-126)
     */
    public function create(array $data): ServiceReturn
    {
        return $this->execute(function () use ($data) {
            $post = $this->repository->create($data);
            return $this->success($post, 'Tạo bài tuyển dụng thành công.');
        }, useTransaction: true);
    }

    /**
     * Cập nhật bài tuyển dụng (UC-126)
     */
    public function update(string $id, array $data): ServiceReturn
    {
        return $this->execute(function () use ($id, $data) {
            $post = $this->repository->findById($id);
            
            $this->validate($post !== null, 'Không tìm thấy bài tuyển dụng phù hợp.', 404);

            $this->repository->updateById($id, $data);
            
            $updatedPost = $this->repository->findById($id);

            return $this->success($updatedPost, 'Cập nhật bài tuyển dụng thành công.');
        }, useTransaction: true);
    }

    /**
     * Xóa bài tuyển dụng (UC-126)
     */
    public function delete(string $id): ServiceReturn
    {
        return $this->execute(function () use ($id) {
            $post = $this->repository->findById($id);
            
            $this->validate($post !== null, 'Bài tuyển dụng không tồn tại.', 404);

            $this->repository->updateById($id, [
                'status' => \App\Modules\Recruitment\Models\Enums\RecruitmentPostStatus::HIDDEN->value
            ]);

            return $this->success(null, 'Ẩn bài tuyển dụng thành công.');
        }, useTransaction: true);
    }
}
