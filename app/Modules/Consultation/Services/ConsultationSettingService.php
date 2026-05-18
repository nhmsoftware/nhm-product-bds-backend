<?php

namespace App\Modules\Consultation\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Consultation\Interfaces\ConsultationSettingRepositoryInterface;
use App\Modules\Consultation\Interfaces\ConsultationSettingServiceInterface;

/**
 * Class ConsultationSettingService
 *
 * @package App\Modules\Consultation\Services
 */
final class ConsultationSettingService extends BaseService implements ConsultationSettingServiceInterface
{
    /**
     * ConsultationSettingService constructor.
     *
     * @param ConsultationSettingRepositoryInterface $consultationSettingRepository
     */
    public function __construct(
        private readonly ConsultationSettingRepositoryInterface $consultationSettingRepository
    ) {
    }

    /**
     * Lấy cấu hình liên hệ tư vấn đang hoạt động của hệ thống.
     *
     * @return ServiceReturn
     */
    public function getActiveSetting(): ServiceReturn
    {
        return $this->execute(function () {
            $setting = $this->consultationSettingRepository->getActiveSetting();

            // A1 – Không có dữ liệu liên hệ
            $this->validate($setting !== null, 'Thông tin liên hệ đang được cập nhật.', 404);

            return $this->success($setting, 'Tải thông tin liên hệ tư vấn thành công.');
        });
    }
}
