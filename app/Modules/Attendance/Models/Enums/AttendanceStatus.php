<?php

namespace App\Modules\Attendance\Models\Enums;

use App\Core\Traits\EnumHelper;

enum AttendanceStatus: int
{
    use EnumHelper;

    case PRESENT = 1;     // Đủ 6h trở lên (hoặc check-out sau 6h)
    case LATE = 2;        // Đi muộn nhưng đủ 6h
    case ABSENT = 3;      // Vắng mặt / dưới 6h & config = 0 công
    case HALF_DAY = 4;    // Dưới 6h (0.5 công) hoặc thiếu check-out
    case WORKING = 5;     // Đã check-in, đang làm việc (chưa check-out)

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
            self::WORKING => 'Đang làm việc',
        };
    }
}
