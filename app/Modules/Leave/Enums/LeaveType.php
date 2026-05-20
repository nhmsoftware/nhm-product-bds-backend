<?php

namespace App\Modules\Leave\Enums;

enum LeaveType: string
{
    case ANNUAL = 'annual';
    case UNPAID = 'unpaid';
    case PERSONAL = 'personal';
    case MATERNITY = 'maternity';
    case BUSINESS = 'business';
    case COMPENSATORY = 'compensatory';

    /**
     * Lấy tên hiển thị tiếng Việt tương ứng cho từng loại nghỉ phép.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::ANNUAL => 'Nghỉ phép năm',
            self::UNPAID => 'Nghỉ không lương',
            self::PERSONAL => 'Nghỉ cá nhân',
            self::MATERNITY => 'Nghỉ thai sản',
            self::BUSINESS => 'Nghỉ công tác',
            self::COMPENSATORY => 'Nghỉ bù',
        };
    }

    /**
     * Lấy tất cả giá trị dạng string của enum.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
