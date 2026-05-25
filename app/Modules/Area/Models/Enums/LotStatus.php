<?php

declare(strict_types=1);

namespace App\Modules\Area\Models\Enums;

use App\Core\Traits\EnumHelper;

enum LotStatus: int
{
    use EnumHelper;

    case AVAILABLE = 1;      // Còn hàng
    case SOLD = 2;           // Đã bán
    case RESERVED = 3;       // Đang giữ chỗ
    case UNAVAILABLE = 4;    // Không khả dụng

    /**
     * Lấy nhãn tiếng Việt tương ứng cho từng trạng thái lô đất.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Còn hàng',
            self::SOLD => 'Đã bán',
            self::RESERVED => 'Đang giữ chỗ',
            self::UNAVAILABLE => 'Không khả dụng',
        };
    }
}
