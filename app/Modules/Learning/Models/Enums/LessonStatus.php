<?php

declare(strict_types=1);

namespace App\Modules\Learning\Models\Enums;

use App\Core\Traits\EnumHelper;

/**
 * Trạng thái hiển thị của một bài học (lesson) đối với học viên.
 * Đây là computed status (tính toán ở application layer), không lưu trực tiếp vào DB.
 */
enum LessonStatus: int
{
    use EnumHelper;

    case COMPLETED = 1;  // Bài học đã hoàn thành
    case LEARNING  = 2;  // Bài học đang được học (mở khóa)
    case LOCKED    = 3;  // Bài học đang bị khóa

    /**
     * Lấy nhãn tiếng Việt tương ứng cho từng trạng thái bài học.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::COMPLETED => 'Hoàn thành',
            self::LEARNING  => 'Đang học',
            self::LOCKED    => 'Khóa',
        };
    }
}
