<?php

namespace App\Filament\Support;

use App\Modules\Branch\Models\Branch;
use App\Modules\Project\Models\Project as ProjectModel;
use Illuminate\Support\Facades\Schema;

final class AdminOptions
{
    public static function branches(): array
    {
        if (Schema::hasTable('branches')) {
            $branches = Branch::query()
                ->where('is_active', true)
                ->orderBy('sort')
                ->orderBy('name')
                ->pluck('name', 'name')
                ->all();

            if ($branches !== []) {
                return $branches;
            }
        }

        return ProjectModel::query()
            ->whereNotNull('branch')
            ->where('branch', '!=', '')
            ->distinct()
            ->orderBy('branch')
            ->pluck('branch', 'branch')
            ->all();
    }

    public static function departments(): array
    {
        return [
            'HN' => 'Hà Nội',
            'HCM' => 'Hồ Chí Minh',
            'DN' => 'Đà Nẵng',
            'Kinh doanh' => 'Kinh doanh',
            'Marketing' => 'Marketing',
            'Đào tạo' => 'Đào tạo',
            'Pháp chế' => 'Pháp chế',
            'Chăm sóc khách hàng' => 'Chăm sóc khách hàng',
            'Nhân sự' => 'Nhân sự',
            'Kế toán' => 'Kế toán',
            'Công nghệ' => 'Công nghệ',
            'ALL' => 'Toàn bộ',
            'SYSTEM' => 'Hệ thống',
        ];
    }

    public static function areas(): array
    {
        return [
            'Hà Nội' => 'Hà Nội',
            'Hồ Chí Minh' => 'Hồ Chí Minh',
            'Đà Nẵng' => 'Đà Nẵng',
            'Miền Bắc' => 'Miền Bắc',
            'Miền Trung' => 'Miền Trung',
            'Miền Nam' => 'Miền Nam',
            'Toàn quốc' => 'Toàn quốc',
        ];
    }

    public static function newsCategories(): array
    {
        return [
            'market' => 'Thị trường',
            'legal' => 'Pháp lý',
            'investment' => 'Đầu tư',
            'project' => 'Dự án',
            'company' => 'Nội bộ công ty',
            'training' => 'Đào tạo',
        ];
    }

    public static function projectTypes(): array
    {
        return self::mergeDistinct([
            'Khu nhà ở sinh thái' => 'Khu nhà ở sinh thái',
            'Khu đô thị' => 'Khu đô thị',
            'Biệt thự' => 'Biệt thự',
            'Liền kề' => 'Liền kề',
            'Shophouse' => 'Shophouse',
            'Đất nền' => 'Đất nền',
            'Căn hộ' => 'Căn hộ',
            'Nghỉ dưỡng' => 'Nghỉ dưỡng',
        ], ProjectModel::query()
            ->whereNotNull('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type', 'type')
            ->all());
    }

    public static function normalizeMoney(mixed $state): mixed
    {
        return is_string($state) ? str_replace(',', '', $state) : $state;
    }

    public static function legalVideoCategories(): array
    {
        return [
            'legal' => 'Pháp lý',
            'planning' => 'Quy hoạch',
            'contract' => 'Hợp đồng',
            'tax' => 'Thuế phí',
            'investment' => 'Đầu tư',
        ];
    }

    private static function mergeDistinct(array $base, array $extra): array
    {
        foreach ($extra as $value => $label) {
            if (is_string($value) && $value !== '') {
                $base[$value] = (string) $label;
            }
        }

        return $base;
    }
}
