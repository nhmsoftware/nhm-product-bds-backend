<?php

declare(strict_types=1);

namespace App\Modules\Area\Models\Enums;

use App\Core\Traits\EnumHelper;

enum LotDepositRequestStatus: int
{
    use EnumHelper;

    case PENDING = 1;     // Chờ xác nhận
    case APPROVED = 2;    // Đã duyệt
    case REJECTED = 3;    // Từ chối
    case COMPLETED = 4;   // Công chứng thành công

    /**
     * Lấy nhãn tiếng Việt tương ứng cho từng trạng thái yêu cầu đặt cọc.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Chờ xác nhận',
            self::APPROVED => 'Đã duyệt',
            self::REJECTED => 'Từ chối',
            self::COMPLETED => 'Công chứng thành công',
        };
    }
}
