<?php

namespace App\Filament\Support;

use Filament\Forms\Components\FileUpload;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

final class AdminUploads
{
    public static function image(string $name, string $label, string $directory): FileUpload
    {
        return FileUpload::make($name)
            ->label($label)
            ->disk('public')
            ->directory($directory)
            ->visibility('public')
            ->image()
            ->imageEditor()
            ->downloadable()
            ->openable()
            ->afterStateHydrated(function (FileUpload $component, mixed $state): void {
                $livewire = $component->getContainer()->getLivewire();
                // Đọc trực tiếp từ Livewire data — tránh $state param stale từ record
                $currentState = data_get($livewire, $component->getStatePath());
                if (is_array($currentState) && !empty(array_filter($currentState))) {
                    $component->state($currentState);
                    return;
                }
                // Ảnh hiện có KHÔNG nạp vào FilePond — tránh "Đang chờ kích thước" spinner
                // khi APP_URL khác với URL thực của server. Dùng helperText để preview thay thế.
                $component->state([]);
            })
            ->helperText(function (FileUpload $component) use ($name): HtmlString {
                $record = $component->getRecord();
                $existing = $record?->getRawOriginal($name) ?? $record?->{$name} ?? null;
                if (blank($existing)) {
                    return new HtmlString('');
                }
                $url = self::toAbsoluteUrl((string) $existing);
                return new HtmlString(
                    "<div style='margin-top:8px'>"
                    . "<img src='" . e($url) . "' alt=''"
                    . " style='max-height:120px;border-radius:6px;border:1px solid #e5e7eb;object-fit:cover;'>"
                    . "</div>"
                );
            })
            ->dehydrateStateUsing(function (FileUpload $component, mixed $state) use ($name): mixed {
                $values = is_array($state) ? array_values(array_filter($state)) : [];

                if (empty($values)) {
                    $record = $component->getRecord();
                    return $record?->getRawOriginal($name) ?? $record?->{$name} ?? null;
                }

                return self::singleWithStoragePrefix($state);
            });
    }

    public static function video(string $name, string $label, string $directory): FileUpload
    {
        return FileUpload::make($name)
            ->label($label)
            ->disk('public')
            ->directory($directory)
            ->visibility('public')
            ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska'])
            ->maxSize(1024 * 1024) // 1GB in KB
            ->downloadable()
            ->openable()
            ->afterStateHydrated(function (FileUpload $component, mixed $state): void {
                // Video KHÔNG nạp vào FilePond — file lớn gây spinner vô tận khi FilePond
                // cố fetch file info qua URL dựa trên APP_URL (có thể sai với server thực).
                // Existing URL được giữ qua dehydrateStateUsing's getRawOriginal fallback.
                if (is_array($state) && !empty(array_filter($state))) {
                    $component->state($state);
                    return;
                }
                $component->state([]);
            })
            ->helperText(function (FileUpload $component) use ($name): HtmlString {
                $record = $component->getRecord();
                $existing = $record?->getRawOriginal($name) ?? $record?->{$name} ?? null;
                if (blank($existing)) {
                    return new HtmlString('');
                }
                $url = self::toAbsoluteUrl((string) $existing);
                return new HtmlString(
                    "<div style='margin-top:8px'>"
                    . "<video controls style='width:100%;max-height:360px;border-radius:8px;background:#000;display:block'>"
                    . "<source src='" . e($url) . "'>"
                    . "Trình duyệt không hỗ trợ phát video."
                    . "</video>"
                    . "<p style='font-size:0.8em;color:#6b7280;margin-top:6px'>"
                    . "Tải lên tệp mới để thay thế video trên."
                    . "</p>"
                    . "</div>"
                );
            })
            ->dehydrateStateUsing(function (FileUpload $component, mixed $state) use ($name): mixed {
                $values = is_array($state) ? array_values(array_filter($state)) : [];

                if (empty($values)) {
                    $record = $component->getRecord();
                    return $record?->getRawOriginal($name) ?? $record?->{$name} ?? null;
                }

                return self::singleWithStoragePrefix($state);
            });
    }

    public static function images(string $name, string $label, string $directory): FileUpload
    {
        return FileUpload::make($name)
            ->label($label)
            ->disk('public')
            ->directory($directory)
            ->visibility('public')
            ->image()
            ->imageEditor()
            ->downloadable()
            ->openable()
            ->multiple()
            ->reorderable()
            ->afterStateHydrated(function (FileUpload $component, mixed $state): void {
                $state = self::stripStoragePrefix($state);

                if (is_string($state) && $state !== '') {
                    $state = [(string) Str::uuid() => $state];
                }

                $component->state(is_array($state) ? $state : []);
            })
            ->dehydrateStateUsing(fn (mixed $state) => self::withStoragePrefix($state));
    }

    /**
     * Chuyển path DB (/storage/... hoặc http://...) thành absolute URL
     * dùng domain của request hiện tại, tránh phụ thuộc APP_URL config.
     */
    private static function toAbsoluteUrl(string $stored): string
    {
        if (self::isExternalUrl($stored)) {
            return $stored;
        }
        $path = ltrim((string) preg_replace('#^/?storage/#', 'storage/', $stored), '/');
        return request()->getSchemeAndHttpHost() . '/' . $path;
    }

    private static function isExternalUrl(string $value): bool
    {
        return str_starts_with($value, 'http://') || str_starts_with($value, 'https://');
    }

    private static function stripStoragePrefix(mixed $state): mixed
    {
        if (is_array($state)) {
            return array_map(fn ($item) => self::stripStoragePrefix($item), $state);
        }

        if (! is_string($state) || $state === '') {
            return $state;
        }

        return preg_replace('#^/?storage/#', '', $state) ?: $state;
    }

    private static function singleWithStoragePrefix(mixed $state): mixed
    {
        if (is_array($state)) {
            $values = array_values(array_filter($state));

            // Prefer newly-uploaded local paths over external URLs or /storage/ paths.
            foreach ($values as $value) {
                if (is_string($value)
                    && ! self::isExternalUrl($value)
                    && ! str_starts_with($value, '/storage/')
                ) {
                    return self::withStoragePrefix($value);
                }
            }

            return self::withStoragePrefix($values[0] ?? null);
        }

        return self::withStoragePrefix($state);
    }

    private static function withStoragePrefix(mixed $state): mixed
    {
        if (is_array($state)) {
            return array_values(array_map(fn ($item) => self::withStoragePrefix($item), array_filter($state)));
        }

        if (! is_string($state) || $state === '') {
            return $state;
        }

        if (self::isExternalUrl($state) || str_starts_with($state, '/storage/')) {
            return $state;
        }

        return '/storage/' . ltrim($state, '/');
    }
}
