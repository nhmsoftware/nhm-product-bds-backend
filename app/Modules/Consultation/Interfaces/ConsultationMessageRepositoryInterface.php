<?php

namespace App\Modules\Consultation\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Consultation\Models\ConsultationMessage;

/**
 * Interface ConsultationMessageRepositoryInterface
 *
 * @package App\Modules\Consultation\Interfaces
 */
interface ConsultationMessageRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Lưu tin nhắn yêu cầu tư vấn mới vào cơ sở dữ liệu.
     *
     * @param array $attributes
     * @return ConsultationMessage
     */
    public function saveMessage(array $attributes): ConsultationMessage;
}
