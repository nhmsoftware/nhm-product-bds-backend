<?php

declare(strict_types=1);

namespace App\Modules\Area\Models\Enums;

use App\Core\Traits\EnumHelper;

enum LotLockRequestStatus: int
{
    use EnumHelper;

    case APPROVED = 2;
    case REJECTED = 3;
    case EXPIRED = 4;

    public function label(): string
    {
        return match ($this) {
            self::APPROVED => 'Thành công',
            self::REJECTED => 'Từ chối',
            self::EXPIRED => 'Hết hạn',
        };
    }
}
