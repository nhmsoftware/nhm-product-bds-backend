<?php

declare(strict_types=1);

namespace App\Modules\Area\Models\Enums;

use App\Core\Traits\EnumHelper;

enum LotLockRequestStatus: int
{
    use EnumHelper;

    case PENDING = 1;
    case APPROVED = 2;
    case REJECTED = 3;
    case EXPIRED = 4;
    case CANCELLED = 5;

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Chờ duyệt',
            self::APPROVED => 'Đã duyệt',
            self::REJECTED => 'Từ chối',
            self::EXPIRED => 'Hết hạn',
            self::CANCELLED => 'Đã hủy',
        };
    }
}
