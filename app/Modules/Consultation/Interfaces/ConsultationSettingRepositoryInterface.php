<?php

namespace App\Modules\Consultation\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Consultation\Models\ConsultationSetting;

/**
 * Interface ConsultationSettingRepositoryInterface
 *
 * @package App\Modules\Consultation\Interfaces
 */
interface ConsultationSettingRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Lấy cấu hình liên hệ tư vấn đang hoạt động đầu tiên.
     *
     * @return ConsultationSetting|null
     */
    public function getActiveSetting(): ?ConsultationSetting;
}
