<?php

namespace App\Modules\Auth\Models\Enums;

use App\Core\Traits\EnumHelper;

enum UserRole: int
{
    use EnumHelper;

    case ADMIN = 1;
    case AGENT = 2;
    case BROKER = 3;
    case BUYER = 4;

    /**
     * Lấy nhãn tiếng Việt tương ứng cho từng vai trò.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Quản trị viên',
            self::AGENT => 'Nhân viên môi giới',
            self::BROKER => 'Môi giới liên kết',
            self::BUYER => 'Khách hàng/Người mua',
        };
    }
}
