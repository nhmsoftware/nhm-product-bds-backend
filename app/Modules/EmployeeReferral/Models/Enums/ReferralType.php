<?php

declare(strict_types=1);

namespace App\Modules\EmployeeReferral\Models\Enums;

use App\Core\Traits\EnumHelper;

enum ReferralType: int
{
    use EnumHelper;

    case RECRUITMENT = 1;
    case CUSTOMER = 2;

    /**
     * Lấy nhãn tiếng Việt tương ứng cho loại QR.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::RECRUITMENT => 'QR tuyển dụng',
            self::CUSTOMER => 'QR giới thiệu khách hàng',
        };
    }
}
