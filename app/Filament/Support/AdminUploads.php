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
                $state = self::stripStoragePrefix($state);

                if (is_array($state)) {
                    $component->state($state);
                    return;
                }

                if (is_string($state) && $state !== '') {
                    $component->state([(string) Str::uuid() => $state]);
                    return;
                }

                $component->state([]);
            })
            ->dehydrateStateUsing(fn (mixed $state) => self::singleWithStoragePrefix($state));
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
            $state = array_values(array_filter($state));
            return self::withStoragePrefix($state[0] ?? null);
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

        if (str_starts_with($state, 'http://') || str_starts_with($state, 'https://') || str_starts_with($state, '/storage/')) {
            return $state;
        }

        return '/storage/' . ltrim($state, '/');
    }
}
