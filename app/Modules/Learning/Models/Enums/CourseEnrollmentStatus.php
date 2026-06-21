<?php

namespace App\Modules\Learning\Models\Enums;

use App\Core\Traits\EnumHelper;

enum CourseEnrollmentStatus: int
{
    use EnumHelper;

    case NOT_STARTED        = 1;
    case IN_PROGRESS        = 2;
    case COMPLETED          = 3;
    case PENDING_GRADING    = 4;
    case PENDING_ONBOARDING = 5;

    public function label(): string
    {
        return match ($this) {
            self::NOT_STARTED        => 'Chưa bắt đầu',
            self::IN_PROGRESS        => 'Đang học',
            self::COMPLETED          => 'Hoàn thành',
            self::PENDING_GRADING    => 'Chờ chấm',
            self::PENDING_ONBOARDING => 'Chờ duyệt',
        };
    }
}
