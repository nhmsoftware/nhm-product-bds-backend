<?php

namespace App\Modules\Consultation\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Consultation\Interfaces\ConsultationSettingRepositoryInterface;
use App\Modules\Consultation\Models\ConsultationSetting;

/**
 * Class ConsultationSettingRepository
 *
 * @package App\Modules\Consultation\Repositories
 */
final class ConsultationSettingRepository extends BaseRepository implements ConsultationSettingRepositoryInterface
{
    /**
     * Xác định tên lớp Model đại diện cho repository này.
     * 
     * @return string
     */
    public function getModel(): string
    {
        return ConsultationSetting::class;
    }

    /**
     * Lấy cấu hình liên hệ tư vấn đang hoạt động đầu tiên.
     *
     * @return ConsultationSetting|null
     */
    public function getActiveSetting(): ?ConsultationSetting
    {
        return $this->model->newQuery()
            ->where('is_active', true)
            ->first();
    }
}
