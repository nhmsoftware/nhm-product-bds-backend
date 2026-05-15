<?php

namespace App\Modules\Planning\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Planning\DTO\PlanningListDTO;
use App\Modules\Planning\Interfaces\PlanningRepositoryInterface;
use App\Modules\Planning\Interfaces\PlanningServiceInterface;

/**
 * Class PlanningService
 * 
 * @package App\Modules\Planning\Services
 */
final class PlanningService extends BaseService implements PlanningServiceInterface
{
    /**
     * PlanningService constructor.
     * 
     * @param PlanningRepositoryInterface $planningRepository
     */
    public function __construct(
        private readonly PlanningRepositoryInterface $planningRepository
    ) {
    }

    /**
     * Lấy danh sách quy hoạch công khai.
     *
     * @param PlanningListDTO $dto
     * @return ServiceReturn
     */
    public function getPublicList(PlanningListDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $paginator = $this->planningRepository->getList($dto);

            $message = $paginator->total() > 0 
                ? 'Tải danh sách quy hoạch thành công.' 
                : 'Không tìm thấy thông tin quy hoạch phù hợp.';

            return $this->success([
                'data' => $paginator->items(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ], $message);
        });
    }

    /**
     * Tìm kiếm quy hoạch.
     *
     * @param string $keyword
     * @param int $perPage
     * @param int $page
     * @return ServiceReturn
     */
    public function search(string $keyword, int $perPage = 10, int $page = 1): ServiceReturn
    {
        return $this->execute(function () use ($keyword, $perPage, $page) {
            $dto = new PlanningListDTO(
                search: $keyword,
                perPage: $perPage,
                page: $page
            );

            $paginator = $this->planningRepository->getList($dto);

            $message = $paginator->total() > 0 
                ? "Tìm thấy {$paginator->total()} kết quả phù hợp." 
                : 'Không tìm thấy thông tin quy hoạch phù hợp.';

            return $this->success([
                'data' => $paginator->items(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ], $message);
        });
    }

    /**
     * Lấy chi tiết quy hoạch.
     *
     * @param string $id
     * @return ServiceReturn
     */
    public function getDetail(string $id): ServiceReturn
    {
        return $this->execute(function () use ($id) {
            $planning = $this->planningRepository->findById($id);

            // A1 – Quy hoạch không tồn tại
            $this->validate($planning !== null, 'Thông tin quy hoạch không tồn tại hoặc đã bị xóa.', 404);

            // A2 – Quy hoạch chưa được công khai (Giả định status 'public' là công khai)
            $this->validate(
                strtolower($planning->status) === 'public' || strtolower($planning->status) === 'published', 
                'Thông tin quy hoạch hiện chưa được công khai.', 
                403
            );

            return $this->success($planning, 'Tải chi tiết quy hoạch thành công.');
        });
    }

    /**
     * Lấy danh sách các tỉnh/thành phố dùng để lọc.
     *
     * @return ServiceReturn
     */
    public function getFilterCities(): ServiceReturn
    {
        return $this->execute(function () {
            $cities = $this->planningRepository->getAvailableCities();

            return $this->success($cities, 'Tải danh sách tỉnh/thành phố thành công.');
        });
    }

    /**
     * Lấy link tải hồ sơ PDF quy hoạch.
     *
     * @param string $id
     * @return ServiceReturn
     */
    public function getDownloadLink(string $id): ServiceReturn
    {
        return $this->execute(function () use ($id) {
            $planning = $this->planningRepository->findById($id);

            $this->validate($planning !== null, 'Thông tin quy hoạch không tồn tại hoặc đã bị xóa.', 404);
            
            // A1 – Chưa có file PDF quy hoạch
            $this->validate(!empty($planning->pdf_url), 'File PDF quy hoạch đang được cập nhật.', 404);

            return $this->success([
                'url' => $planning->pdf_url,
                'title' => $planning->title,
            ], 'Tải PDF quy hoạch thành công.');
        });
    }
}
