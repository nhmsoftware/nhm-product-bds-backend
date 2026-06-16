<?php

namespace App\Modules\SiteTour\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use App\Modules\SiteTour\DTO\CheckInSiteTourDTO;
use App\Modules\SiteTour\Events\SiteTourCheckedIn;
use App\Modules\SiteTour\Interfaces\SiteTourRepositoryInterface;
use App\Modules\SiteTour\Interfaces\SiteTourServiceInterface;
use App\Modules\Area\Interfaces\AreaRepositoryInterface;
use Illuminate\Support\Facades\Storage;
use App\Modules\Auth\Models\Enums\UserRole;

final class SiteTourService extends BaseService implements SiteTourServiceInterface
{
    public function __construct(
        private readonly SiteTourRepositoryInterface $siteTourRepository,
        private readonly AuthRepositoryInterface $authRepository,
        private readonly AreaRepositoryInterface $areaRepository
    ) {
    }

    /**
     * Thực hiện check-in hoạt động dẫn khách tham quan dự án/lô đất.
     *
     * @param CheckInSiteTourDTO $dto Dữ liệu check-in dẫn khách
     * @return ServiceReturn Trả về kết quả lưu hoạt động dẫn khách thành công hoặc thất bại
     */
    public function checkInSiteTour(CheckInSiteTourDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // 1. Kiểm tra tài khoản và trạng thái hoạt động của nhân viên
            $user = $this->authRepository->findById($dto->userId);
            
            $this->validate(
                $user !== null,
                'Không thể tải thông tin nhân viên.',
                404
            );

            $this->validate(
                (bool) $user->is_active,
                'Tài khoản của bạn đã bị khóa hoặc đang ngưng hoạt động.',
                403
            );

            // Kiểm tra phân quyền nhân viên (admin, agent, broker)
            $this->validate(
                in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::CEO, UserRole::DIRECTOR, UserRole::MANAGER, UserRole::EMPLOYEE], true),
                'Bạn không có quyền thực hiện chức năng dẫn khách.',
                403
            );

            // 2. Kiểm tra dự án tồn tại
            $area = $this->areaRepository->findById($dto->projectId);
            $this->validate(
                $area !== null,
                'Khu đất quan tâm không tồn tại trên hệ thống.',
                404
            );

            // 3. Kiểm tra tọa độ GPS hợp lệ (A5)
            // Lấy tọa độ từ .env, mặc định là Khu 25 Thửa phú cát nếu không có
            $projectLat = (float) env('DEFAULT_PROJECT_LAT', 21.04039963677646);
            $projectLng = (float) env('DEFAULT_PROJECT_LNG', 105.77333406318525);

            $distance = $this->calculateDistance($dto->latitude, $dto->longitude, $projectLat, $projectLng);
            $this->validate(
                $distance <= 1000, // Phạm vi bán kính 1km từ tâm dự án
                'Bạn không nằm trong khu vực hợp lệ.',
                422
            );

            // 4. Lưu ảnh minh chứng thực tế
            $imagePath = $dto->image->store('site_tours', 'public');
            $this->validate(
                $imagePath !== false && $imagePath !== null,
                'Không thể lưu hình ảnh minh chứng. Vui lòng thử lại.',
                500
            );

            // 5. Tạo bản ghi hoạt động dẫn khách (Site Tour)
            $siteTourData = $dto->toArray();
            $siteTourData['image_path'] = Storage::url($imagePath);

            $siteTour = $this->siteTourRepository->create($siteTourData);
            $this->validate(
                $siteTour !== null,
                'Không thể lưu hoạt động dẫn khách. Vui lòng thử lại.',
                500
            );

            // 6. Bắn Domain Event kích hoạt các Listener (cộng điểm KPI, realtime update)
            event(new SiteTourCheckedIn($siteTour));

            // Lấy lại danh sách hoạt động gần đây để cập nhật giao diện
            $recentTours = $this->siteTourRepository->getRecentToursByUserId($dto->userId, 5);

            return $this->success([
                'site_tour' => $siteTour,
                'recent_tours' => $recentTours
            ], 'Check-in dẫn khách thành công.', 201);
        }, useTransaction: true);
    }

    /**
     * Lấy danh sách các hoạt động dẫn khách gần đây nhất của nhân viên.
     *
     * @param string $userId ID của nhân viên (UUID)
     * @param int $limit Số lượng bản ghi tối đa
     * @return ServiceReturn Trả về danh sách các hoạt động dẫn khách
     */
    public function getRecentTours(string $userId, int $limit = 5): ServiceReturn
    {
        return $this->execute(function () use ($userId, $limit) {
            $user = $this->authRepository->findById($userId);
            $this->validate($user !== null, 'Không thể tải thông tin tài khoản.', 404);
            $this->validate((bool) $user->is_active, 'Tài khoản đã bị khóa.', 403);

            $tours = $this->siteTourRepository->getRecentToursByUserId($userId, $limit);

            return $this->success($tours, 'Tải danh sách hoạt động dẫn khách thành công.');
        });
    }

    /**
     * Lấy lịch sử dẫn khách tham quan kèm bộ lọc của nhân viên.
     *
     * @param string $userId ID của nhân viên (UUID)
     * @param array $filters Bộ lọc tìm kiếm
     * @return ServiceReturn Trả về danh sách lịch sử dẫn khách
     */
    public function getSiteTourHistory(string $userId, array $filters = []): ServiceReturn
    {
        return $this->execute(function () use ($userId, $filters) {
            // 1. Kiểm tra tài khoản và trạng thái hoạt động của nhân viên
            $user = $this->authRepository->findById($userId);
            $this->validate(
                $user !== null,
                'Không thể tải thông tin nhân viên.',
                404
            );

            $this->validate(
                (bool) $user->is_active,
                'Tài khoản của bạn đã bị khóa hoặc đang ngưng hoạt động.',
                403
            );

            // Kiểm tra phân quyền nhân viên (admin, agent, broker)
            $this->validate(
                in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::CEO, UserRole::DIRECTOR, UserRole::MANAGER, UserRole::EMPLOYEE], true),
                'Bạn không có quyền thực hiện chức năng dẫn khách.',
                403
            );

            // 2. Tải lịch sử dẫn khách từ Repository với bắt lỗi ngoại lệ (A2)
            try {
                $tours = $this->siteTourRepository->getTourHistory($userId, $filters);

                // A3 - Không có dữ liệu phù hợp (khi có bộ lọc mà không ra kết quả)
                if ($tours->isEmpty() && (!empty($filters['project_id']) || !empty($filters['customer_name']))) {
                    return $this->success($tours, 'Không có dữ liệu lịch sử phù hợp.');
                }

                // A1 - Không có lịch sử dẫn khách nào
                if ($tours->isEmpty()) {
                    return $this->success($tours, 'Chưa có lịch sử dẫn khách.');
                }

                return $this->success($tours, 'Tải danh sách lịch sử dẫn khách thành công.');
            } catch (\Exception $e) {
                // A2 - Lỗi tải dữ liệu
                return $this->error('Không thể tải lịch sử dẫn khách. Vui lòng thử lại.', 500);
            }
        });
    }

    /**
     * Tính khoảng cách Haversine giữa 2 tọa độ (mét).
     */
    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // mét

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
