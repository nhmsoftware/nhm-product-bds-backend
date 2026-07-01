<?php

namespace App\Modules\Auth\Models\Enums;

use App\Core\Traits\EnumHelper;

/**
 * @deprecated Sử dụng bảng roles động thay vì enum này.
 * Chỉ giữ lại cho migration data mapping (giá trị tinyInt cũ → role name mới).
 * Toàn bộ runtime check nên dùng $user->hasPermission() hoặc $user->role?->name.
 */
enum UserRole: int
{
    use EnumHelper;

    case EMPLOYEE = 1;
    case MANAGER = 2;
    case DIRECTOR = 3;
    case CEO = 4;
    case SUPER_ADMIN = 5;
    case BUYER = 6;

    public function label(): string
    {
        return match ($this) {
            self::EMPLOYEE => 'Chuyên viên kinh doanh',
            self::MANAGER => 'Trưởng phòng kinh doanh',
            self::DIRECTOR => 'Giám đốc kinh doanh',
            self::CEO => 'Tổng giám đốc',
            self::SUPER_ADMIN => 'Super Admin',
            self::BUYER => 'Khách hàng',
        };
    }

    /**
     * Map giá trị tinyInt cũ → role name mới trong bảng roles.
     * Dùng trong migration để convert dữ liệu.
     */
    public function toRoleName(): string
    {
        return match ($this) {
            self::EMPLOYEE => 'employee',
            self::MANAGER => 'tp_kd',
            self::DIRECTOR => 'gdkd',
            self::CEO => 'ceo',
            self::SUPER_ADMIN => 'super_admin',
            self::BUYER => 'buyer',
        };
    }
}
