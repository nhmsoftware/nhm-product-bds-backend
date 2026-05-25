<?php

declare(strict_types=1);

namespace App\Modules\Project\Models\Enums;

use App\Core\Traits\EnumHelper;

/**
 * Trạng thái mở bán của dự án bất động sản.
 */
enum ProjectStatus: int
{
    use EnumHelper;

    case OPENING = 1;     // Đang mở bán
    case COMING_SOON = 2; // Sắp mở bán
    case SOLD_OUT = 3;    // Đã bán hết

    /**
     * Lấy nhãn tiếng Việt tương ứng cho từng trạng thái.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::OPENING     => 'Đang mở bán',
            self::COMING_SOON => 'Sắp mở bán',
            self::SOLD_OUT    => 'Đã bán hết',
        };
    }
}
