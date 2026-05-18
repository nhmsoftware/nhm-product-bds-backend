<?php

namespace App\Modules\Consultation\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Consultation\Interfaces\ConsultationMessageRepositoryInterface;
use App\Modules\Consultation\Models\ConsultationMessage;

/**
 * Class ConsultationMessageRepository
 *
 * @package App\Modules\Consultation\Repositories
 */
final class ConsultationMessageRepository extends BaseRepository implements ConsultationMessageRepositoryInterface
{
    /**
     * Xác định Model chính của repository này.
     *
     * @return string
     */
    public function getModel(): string
    {
        return ConsultationMessage::class;
    }

    /**
     * Lưu tin nhắn yêu cầu tư vấn mới vào cơ sở dữ liệu.
     *
     * @param array $attributes
     * @return ConsultationMessage
     */
    public function saveMessage(array $attributes): ConsultationMessage
    {
        return $this->create($attributes);
    }
}
