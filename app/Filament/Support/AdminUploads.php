<?php

namespace App\Filament\Support;

use Filament\Forms\Components\FileUpload;
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
                $stripped = self::stripStoragePrefix($state);

                if (is_array($stripped)) {
                    $component->state($stripped);
                    return;
                }

                // External URLs cannot be served through the local disk — the imageEditor
                // calls getImageNode() on the file and crashes. Leave state empty;
                // dehydrateStateUsing will fall back to the record's original value.
                if (is_string($stripped) && self::isExternalUrl($stripped)) {
                    $component->state([]);
                    return;
                }

                if (is_string($stripped) && $stripped !== '') {
                    $component->state([(string) Str::uuid() => $stripped]);
                    return;
                }

                $component->state([]);
            })
            ->dehydrateStateUsing(function (FileUpload $component, mixed $state) use ($name): mixed {
                $values = is_array($state) ? array_values(array_filter($state)) : [];

                // No new file uploaded — preserve the original DB value.
                if (empty($values)) {
                    $record = $component->getContainer()->getLivewire()->record ?? null;
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
                $stripped = self::stripStoragePrefix($state);

                if (is_array($stripped)) {
                    $component->state($stripped);
                    return;
                }

                // External URLs cannot be served through the local disk. Leave state empty;
                // dehydrateStateUsing will fall back to the record's original value.
                if (is_string($stripped) && self::isExternalUrl($stripped)) {
                    $component->state([]);
                    return;
                }

                if (is_string($stripped) && $stripped !== '') {
                    $component->state([(string) Str::uuid() => $stripped]);
                    return;
                }

                $component->state([]);
            })
            ->dehydrateStateUsing(function (FileUpload $component, mixed $state) use ($name): mixed {
                $values = is_array($state) ? array_values(array_filter($state)) : [];

                if (empty($values)) {
                    $record = $component->getContainer()->getLivewire()->record ?? null;
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
