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
     * Hỗ trợ cả field trực tiếp lẫn nested relation (ví dụ: user.avatar).
     */
    public static function make(string $field): ImageColumn
    {
        return ImageColumn::make($field)
            ->getStateUsing(function ($record) use ($field): ?string {
                // Hỗ trợ nested relation: user.avatar → $record->user?->avatar
                $value = null;
                if (str_contains($field, '.')) {
                    $parts = explode('.', $field);
                    $obj = $record;
                    foreach ($parts as $part) {
                        $obj = $obj?->{$part} ?? null;
                    }
                    $value = is_string($obj) ? $obj : null;
                } else {
                    $value = $record->getRawOriginal($field);
                }

                if (empty($value)) {
                    return null;
                }

                // External URL → dùng thẳng
                if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
                    return $value;
                }

                // Local path: đảm bảo có /storage/ prefix rồi build absolute URL
                $path = ltrim($value, '/');
                if (!str_starts_with($path, 'storage/')) {
                    $path = 'storage/' . $path;
                }

                return request()->getSchemeAndHttpHost() . '/' . $path;
            });
    }
}
