<?php

namespace App\Modules\Consultation\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Consultation\DTO\SubmitConsultationDTO;

/**
 * Interface ConsultationMessageServiceInterface
 *
 * @package App\Modules\Consultation\Interfaces
 */
interface ConsultationMessageServiceInterface
{
    /**
     * Gửi tin nhắn yêu cầu tư vấn mới từ phía người dùng.
     *
     * @param SubmitConsultationDTO $dto
     * @return ServiceReturn
     */
    public function submitMessage(SubmitConsultationDTO $dto): ServiceReturn;
}
