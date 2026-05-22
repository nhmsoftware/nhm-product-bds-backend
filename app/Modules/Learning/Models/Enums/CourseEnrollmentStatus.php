<?php

namespace App\Modules\Learning\Models\Enums;

use App\Core\Traits\EnumHelper;

enum CourseEnrollmentStatus: int
{
    use EnumHelper;

    case NOT_STARTED = 1;
    case IN_PROGRESS = 2;
    case COMPLETED = 3;

    /**
     * Lấy nhãn tiếng Việt tương ứng cho từng trạng thái đăng ký học.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::NOT_STARTED => 'Chưa bắt đầu',
            self::IN_PROGRESS => 'Đang học',
            self::COMPLETED => 'Hoàn thành',
        };
    }
}
