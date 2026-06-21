<?php

namespace App\Filament\Support;

use Filament\Tables\Columns\ImageColumn;

final class AdminImageColumn
{
    /**
     * ImageColumn chuẩn cho ảnh lưu qua AdminUploads.
     *
     * Luôn trả về absolute URL dựa trên request hiện tại thay vì APP_URL config —
     * tránh trường hợp admin truy cập qua IP:port khác với APP_URL được cấu hình.
     */
    public static function make(string $field): ImageColumn
    {
        return ImageColumn::make($field)
            ->getStateUsing(function ($record) use ($field): ?string {
                $value = $record->getRawOriginal($field);

                if (empty($value)) {
                    return null;
                }

                // External URL → dùng thẳng
                if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
                    return $value;
                }

                // Local path → tạo absolute URL từ request hiện tại (không dùng APP_URL)
                $path = ltrim((string) preg_replace('#^/?storage/#', 'storage/', $value), '/');
                return request()->getSchemeAndHttpHost() . '/' . $path;
            });
    }
}
