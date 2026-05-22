<?php

namespace App\Modules\Attendance\Models\Enums;

use App\Core\Traits\EnumHelper;

enum AttendanceStatus: int
{
    use EnumHelper;

    case PRESENT = 1;
    case LATE = 2;
    case ABSENT = 3;
    case HALF_DAY = 4;

    /**
     * Lấy nhãn tiếng Việt tương ứng cho từng trạng thái điểm danh.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::PRESENT => 'Có mặt',
            self::LATE => 'Đi muộn',
            self::ABSENT => 'Vắng mặt',
            self::HALF_DAY => 'Nửa ngày',
        };
    }
}
