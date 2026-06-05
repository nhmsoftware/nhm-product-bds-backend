<?php

declare(strict_types=1);

namespace App\Modules\Area\Models\Enums;

use App\Core\Traits\EnumHelper;

/**
 * Trạng thái mở bán của khu đất / bảng hàng.
 */
enum AreaStatus: int
{
    use EnumHelper;

    case OPENING     = 1; // Đang mở bán
    case SOLD_OUT    = 2; // Đã hết hàng
    case COMING_SOON = 3; // Sắp mở bán
    case CLOSED      = 4; // Đã đóng

    /**
     * Lấy nhãn tiếng Việt tương ứng cho từng trạng thái.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::OPENING     => 'Đang mở bán',
            self::SOLD_OUT    => 'Đã hết hàng',
            self::COMING_SOON => 'Sắp mở bán',
            self::CLOSED      => 'Đã đóng',
        };
    }
}
