<?php

namespace App\Modules\Auth\Models\Enums;

use App\Core\Traits\EnumHelper;

enum UserRole: int
{
    use EnumHelper;

    case EMPLOYEE = 1;
    case MANAGER = 2;
    case DIRECTOR = 3;
    case CEO = 4;
    case SUPER_ADMIN = 5;
    case BUYER = 6;

    /**
     * Lấy nhãn tiếng Việt tương ứng cho từng vai trò.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::EMPLOYEE => 'Nhân viên',
            self::MANAGER => 'Trưởng phòng',
            self::DIRECTOR => 'Giám đốc',
            self::CEO => 'Tổng giám đốc',
            self::SUPER_ADMIN => 'Super Admin',
            self::BUYER => 'Khách hàng',
        };
    }
}
