<?php

namespace App\Modules\Leave\Enums;

use App\Core\Traits\EnumHelper;

enum LeaveType: int
{
    use EnumHelper;

    case ANNUAL = 1;
    case UNPAID = 2;
    case PERSONAL = 3;
    case MATERNITY = 4;
    case BUSINESS = 5;
    case COMPENSATORY = 6;

    /**
     * Lấy tên hiển thị tiếng Việt tương ứng cho từng loại nghỉ phép.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::ANNUAL => 'Nghỉ phép năm',
            self::UNPAID => 'Nghỉ không lương',
            self::PERSONAL => 'Nghỉ cá nhân',
            self::MATERNITY => 'Nghỉ thai sản',
            self::BUSINESS => 'Nghỉ công tác',
            self::COMPENSATORY => 'Nghỉ bù',
        };
    }
}

