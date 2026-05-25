<?php

declare(strict_types=1);

namespace App\Modules\Planning\Models\Enums;

use App\Core\Traits\EnumHelper;

enum PlanningStatus: int
{
    use EnumHelper;

    case DRAFT = 1;      // Nháp / Chưa công khai
    case PUBLIC = 2;     // Đã công khai
    case ARCHIVED = 3;   // Đã lưu trữ

    /**
     * Lấy nhãn tiếng Việt tương ứng cho từng trạng thái quy hoạch.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT    => 'Nháp',
            self::PUBLIC   => 'Đã công khai',
            self::ARCHIVED => 'Đã lưu trữ',
        };
    }
}
