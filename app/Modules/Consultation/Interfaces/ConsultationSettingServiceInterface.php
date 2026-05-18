<?php

namespace App\Modules\Consultation\Interfaces;

use App\Core\Services\ServiceReturn;

/**
 * Interface ConsultationSettingServiceInterface
 *
 * @package App\Modules\Consultation\Interfaces
 */
interface ConsultationSettingServiceInterface
{
    /**
     * Lấy cấu hình liên hệ tư vấn đang hoạt động của hệ thống.
     *
     * @return ServiceReturn
     */
    public function getActiveSetting(): ServiceReturn;
}
