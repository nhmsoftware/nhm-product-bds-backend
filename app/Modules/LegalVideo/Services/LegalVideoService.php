<?php

namespace App\Modules\LegalVideo\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\LegalVideo\DTO\GetLegalVideoListDTO;
use App\Modules\LegalVideo\Events\LegalVideoViewed;
use App\Modules\LegalVideo\Interfaces\LegalVideoRepositoryInterface;
use App\Modules\LegalVideo\Interfaces\LegalVideoServiceInterface;

final class LegalVideoService extends BaseService implements LegalVideoServiceInterface
{
    public function __construct(
        private readonly LegalVideoRepositoryInterface $legalVideoRepository
    ) {
    }

    /**
     * Lấy danh sách video kiến thức và pháp lý bất động sản.
     *
     * @param GetLegalVideoListDTO $dto DTO chứa các tham số bộ lọc và phân trang.
     * @return ServiceReturn Đối tượng chứa danh sách video hoặc lỗi nếu xảy ra sự cố.
     * @throws \Throwable
     */
    public function getList(GetLegalVideoListDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $paginatedVideos = $this->legalVideoRepository->getPublishedVideos($dto->toArray());

            $data = [
                'list' => $paginatedVideos->items(),
                'pagination' => [
                    'total' => $paginatedVideos->total(),
                    'per_page' => $paginatedVideos->perPage(),
                    'current_page' => $paginatedVideos->currentPage(),
                    'last_page' => $paginatedVideos->lastPage(),
                ],
                'categories' => $this->getStaticCategories(),
            ];

            $emptyMessage = 'Hiện chưa có video pháp lý.';
            if ($dto->search !== null && trim($dto->search) !== '') {
                $emptyMessage = 'Không tìm thấy video phù hợp.';
            } elseif ($dto->category !== null) {
                $emptyMessage = 'Hiện chưa có video pháp lý trong danh mục này.';
            }

            $this->validate(count($data['list']) > 0, $emptyMessage, 200);

            return $this->success($data, 'Tải danh sách video pháp lý thành công.');
        }, useTransaction: false);
    }

    /**
     * Lấy thông tin chi tiết và phát video.
     *
     * @param string $idOrSlug UUID hoặc Slug của video pháp lý cần xem.
     * @return ServiceReturn Đối tượng chứa thông tin chi tiết của video hoặc lỗi nếu không còn khả dụng.
     * @throws \Throwable
     */
    public function getDetail(string $idOrSlug): ServiceReturn
    {
        return $this->execute(function () use ($idOrSlug) {
            if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $idOrSlug)) {
                $video = $this->legalVideoRepository->find($idOrSlug);
            } else {
                $video = $this->legalVideoRepository->findBySlug($idOrSlug);
            }

            // A1 – Video không tồn tại hoặc đã bị xóa
            $this->validate($video !== null, 'Video không tồn tại hoặc đã bị xóa.', 404);

            // A2 – Video bị ẩn hoặc ngừng hiển thị
            $isPublic = $video->is_active === true && ($video->published_at === null || $video->published_at <= now());
            $this->validate($isPublic, 'Video hiện không khả dụng.', 404);

            // Bắn Domain Event
            $userId = auth()->id();
            event(new LegalVideoViewed($video, $userId));

            $data = [
                'detail' => $video,
            ];

            return $this->success($data, 'Tải chi tiết video thành công.');
        }, useTransaction: false);
    }

    /**
     * Lấy danh sách các danh mục video pháp lý tĩnh.
     *
     * @return array Danh sách danh mục.
     */
    private function getStaticCategories(): array
    {
        return [
            ['id' => 'all', 'name' => 'Tất cả'],
            ['id' => 'project_legal', 'name' => 'Pháp lý dự án'],
            ['id' => 'contract', 'name' => 'Hợp đồng'],
            ['id' => 'planning', 'name' => 'Quy hoạch'],
            ['id' => 'transaction_process', 'name' => 'Quy trình giao dịch'],
            ['id' => 'other', 'name' => 'Khác'],
        ];
    }
}
