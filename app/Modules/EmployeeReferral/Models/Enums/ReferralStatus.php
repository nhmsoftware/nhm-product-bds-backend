<?php

declare(strict_types=1);

namespace App\Modules\EmployeeReferral\Models\Enums;

use App\Core\Traits\EnumHelper;

enum ReferralStatus: int
{
    use EnumHelper;

    case INCOMPLETE = 1;
    case REGISTERED = 2;

    /**
     * Lấy nhãn tiếng Việt tương ứng cho trạng thái đăng ký.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::INCOMPLETE => 'Chưa hoàn tất đăng ký',
            self::REGISTERED => 'Đã đăng ký',
        };
    }
}
