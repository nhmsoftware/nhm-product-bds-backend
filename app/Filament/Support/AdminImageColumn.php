<?php

namespace App\Filament\Support;

use Filament\Tables\Columns\ImageColumn;

final class AdminImageColumn
{
    /**
     * ImageColumn chuẩn cho ảnh lưu qua AdminUploads.
     *
     * Xử lý 2 trường hợp giá trị trong DB:
     * - External URL (http/https) → dùng thẳng làm src
     * - Local path (/storage/...) → strip prefix rồi để public disk tạo URL
     */
    public static function make(string $field): ImageColumn
    {
        return ImageColumn::make($field)
            ->disk('public')
            ->getStateUsing(function ($record) use ($field): ?string {
                $value = $record->getRawOriginal($field);

                if (empty($value)) {
                    return null;
                }

                if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
                    return $value;
                }

                return ltrim((string) preg_replace('#^/?storage/#', '', $value), '/');
            });
    }
}
