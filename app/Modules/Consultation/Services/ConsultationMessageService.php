<?php

namespace App\Modules\Consultation\Services;

use App\Core\Logs\Logging;
use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Consultation\DTO\SubmitConsultationDTO;
use App\Modules\Consultation\Events\ConsultationMessageSubmitted;
use App\Modules\Consultation\Interfaces\ConsultationMessageRepositoryInterface;
use App\Modules\Consultation\Interfaces\ConsultationMessageServiceInterface;

/**
 * Class ConsultationMessageService
 *
 * @package App\Modules\Consultation\Services
 */
final class ConsultationMessageService extends BaseService implements ConsultationMessageServiceInterface
{
    /**
     * ConsultationMessageService constructor.
     *
     * @param ConsultationMessageRepositoryInterface $consultationMessageRepository
     */
    public function __construct(
        private readonly ConsultationMessageRepositoryInterface $consultationMessageRepository
    ) {
    }

    /**
     * Gửi tin nhắn yêu cầu tư vấn mới từ phía người dùng.
     *
     * @param SubmitConsultationDTO $dto
     * @return ServiceReturn
     */
    public function submitMessage(SubmitConsultationDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // 1. Lưu thông tin yêu cầu tư vấn
            $message = $this->consultationMessageRepository->saveMessage($dto->toArray());

            // A4 - Lỗi gửi yêu cầu tư vấn (Nếu không lưu được hoặc có lỗi DB, execute sẽ bắt exception và ném 500)
            $this->validate($message !== null, 'Không thể gửi yêu cầu tư vấn. Vui lòng thử lại.', 500);

            // 2. Gửi thông báo đến bộ phận sale (Represented by Domain Event)
            event(new ConsultationMessageSubmitted($message));

            // Log hành động gửi thông báo tượng trưng đến sale team
            Logging::debug("[UC-026] Yêu cầu tư vấn mới đã được gửi. ID: {$message->id}. Số điện thoại: {$message->phone}. Đã gửi thông báo cho bộ phận Sale.");

            return $this->success($message, 'Gửi yêu cầu tư vấn thành công.');
        }, useTransaction: true);
    }
}
