<?php

declare(strict_types=1);

namespace App\Modules\Project\Models\Enums;

enum InventoryPermission: string
{
    case VIEW_PROJECT = 'view_project';
    case VIEW_AREA = 'view_area';
    case VIEW_LOT = 'view_lot';
    case LOCK_LOT = 'lock_lot';
    case DEPOSIT_LOT = 'deposit_lot';

    public function label(): string
    {
        return match ($this) {
            self::VIEW_PROJECT => 'Xem dự án',
            self::VIEW_AREA => 'Xem bảng hàng',
            self::VIEW_LOT => 'Xem chi tiết lô đất',
            self::LOCK_LOT => 'Thao tác lock lô',
            self::DEPOSIT_LOT => 'Thao tác đặt cọc',
        };
    }
}
