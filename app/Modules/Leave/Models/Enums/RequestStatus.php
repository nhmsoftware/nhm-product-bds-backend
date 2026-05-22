<?php

namespace App\Modules\Leave\Models\Enums;

use App\Core\Traits\EnumHelper;

enum RequestStatus: int
{
    use EnumHelper;

    case PENDING = 1;
    case APPROVED = 2;
    case REJECTED = 3;
    case CANCELLED = 4;

    /**
     * Lấy nhãn tiếng Việt tương ứng cho từng trạng thái yêu cầu.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Đang chờ duyệt',
            self::APPROVED => 'Đã duyệt',
            self::REJECTED => 'Từ chối',
            self::CANCELLED => 'Đã hủy',
        };
    }
}
