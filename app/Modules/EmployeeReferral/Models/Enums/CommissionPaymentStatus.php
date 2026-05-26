<?php

declare(strict_types=1);

namespace App\Modules\EmployeeReferral\Models\Enums;

use App\Core\Traits\EnumHelper;

enum CommissionPaymentStatus: int
{
    use EnumHelper;

    case PENDING = 1;
    case PAID = 2;

    /**
     * Lấy nhãn tiếng Việt tương ứng cho trạng thái thanh toán hoa hồng.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Chờ thanh toán',
            self::PAID => 'Đã thanh toán',
        };
    }
}
