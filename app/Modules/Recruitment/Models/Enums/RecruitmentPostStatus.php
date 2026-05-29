<?php

namespace App\Modules\Recruitment\Models\Enums;

use App\Core\Traits\EnumHelper;

enum RecruitmentPostStatus: int
{
    use EnumHelper;

    case SHOWING = 1;
    case HIDDEN = 2;

    /**
     * Lấy nhãn tiếng Việt tương ứng cho từng trạng thái.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::SHOWING => 'Đang hiển thị',
            self::HIDDEN => 'Đã ẩn',
        };
    }
}
