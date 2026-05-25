<?php

declare(strict_types=1);

namespace App\Modules\Consultation\Models\Enums;

use App\Core\Traits\EnumHelper;

/**
 * Trạng thái xử lý yêu cầu tư vấn.
 */
enum ConsultationStatus: int
{
    use EnumHelper;

    case PENDING   = 1; // Đang chờ xử lý
    case PROCESSED = 2; // Đã xử lý
    case CANCELLED = 3; // Đã hủy

    /**
     * Lấy nhãn tiếng Việt tương ứng cho từng trạng thái.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING   => 'Đang chờ xử lý',
            self::PROCESSED => 'Đã xử lý',
            self::CANCELLED => 'Đã hủy',
        };
    }
}
