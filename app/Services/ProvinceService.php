<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ProvinceService
{
    private const BASE_URL = 'https://provinces.open-api.vn/api';
    private const CACHE_TTL = 86400; // 24 giờ

    /**
     * Lấy danh sách 63 tỉnh/thành phố → [tên => tên].
     */
    public static function provinces(): array
    {
        return Cache::remember('vn_provinces', self::CACHE_TTL, function () {
            $response = Http::timeout(10)->get(self::BASE_URL . '/p/');

            if (!$response->successful()) {
                return [];
            }

            $items = $response->json();
            if (!is_array($items)) return [];

            $result = [];
            foreach ($items as $item) {
                $name = self::stripAdminPrefix($item['name'] ?? '');
                if ($name) {
                    $result[$name] = $name;
                }
            }

            asort($result);
            return $result;
        });
    }

    /**
     * Lấy danh sách quận/huyện theo tỉnh → [tên => tên].
     */
    public static function districts(string $provinceName): array
    {
        if (!$provinceName) return [];

        $cacheKey = 'vn_districts_' . md5($provinceName);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($provinceName) {
            // Tìm code tỉnh
            $allResponse = Http::timeout(10)->get(self::BASE_URL . '/p/');
            if (!$allResponse->successful()) return [];

            $provinces = $allResponse->json();
            if (!is_array($provinces)) return [];

            $code = null;
            foreach ($provinces as $item) {
                $name = self::stripAdminPrefix($item['name'] ?? '');
                if ($name === $provinceName) {
                    $code = $item['code'];
                    break;
                }
            }

            if (!$code) return [];

            // Lấy quận/huyện của tỉnh
            $detailResponse = Http::timeout(10)->get(self::BASE_URL . '/p/' . $code . '?depth=2');
            if (!$detailResponse->successful()) return [];

            $detail = $detailResponse->json();
            $districts = $detail['districts'] ?? [];

            $result = [];
            foreach ($districts as $d) {
                $name = self::stripAdminPrefix($d['name'] ?? '');
                if ($name) {
                    $result[$name] = $name;
                }
            }

            asort($result);
            return $result;
        });
    }

    /**
     * Bỏ tiền tố hành chính: "Tỉnh", "Thành phố", "Quận", "Huyện", "Thị xã", "Thị trấn", "Phường", "Xã".
     */
    private static function stripAdminPrefix(string $name): string
    {
        $prefixes = [
            'Thành phố ', 'Tỉnh ', 'Quận ', 'Huyện ',
            'Thị xã ', 'Thị trấn ', 'Phường ', 'Xã ',
        ];

        foreach ($prefixes as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return substr($name, strlen($prefix));
            }
        }

        return $name;
    }
}
