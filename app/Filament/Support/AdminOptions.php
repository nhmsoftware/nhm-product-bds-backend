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
            'Kinh doanh' => 'Kinh doanh',
            'Marketing' => 'Marketing',
            'Đào tạo' => 'Đào tạo',
            'Pháp chế' => 'Pháp chế',
            'Chăm sóc khách hàng' => 'Chăm sóc khách hàng',
            'Nhân sự' => 'Nhân sự',
            'Kế toán' => 'Kế toán',
            'Công nghệ' => 'Công nghệ',
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
            'market'     => 'Thị trường',
            'legal'      => 'Pháp lý',
            'investment' => 'Đầu tư',
            'project'    => 'Dự án',
            'training'   => 'Đào tạo',
        ];
    }

    public static function newsCategoryLabels(): array
    {
        return [
            ...self::newsCategories(),
            'internal' => 'Nội bộ',
            'company'  => 'Nội bộ',
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

    public static function parseDecimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $value = trim((string) $value);
        $value = str_replace(' ', '', $value); // Loại bỏ khoảng trắng

        // Nếu có cả dấu phẩy và dấu chấm
        if (str_contains($value, ',') && str_contains($value, '.')) {
            $lastComma = strrpos($value, ',');
            $lastDot = strrpos($value, '.');
            if ($lastComma > $lastDot) {
                // Kiểu Việt Nam: 1.500.000,50 -> Xoá dấu chấm, thay phẩy bằng chấm
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                // Kiểu Anh: 1,500,000.50 -> Xoá phẩy
                $value = str_replace(',', '', $value);
            }
        } else {
            // Chỉ chứa phẩy hoặc chỉ chứa chấm
            if (str_contains($value, ',')) {
                if (substr_count($value, ',') > 1) {
                    // Nhiều dấu phẩy: 1,500,000 -> Xoá phẩy
                    $value = str_replace(',', '', $value);
                } else {
                    // 1 dấu phẩy: 100,1 hoặc 1,500
                    $afterComma = substr($value, strrpos($value, ',') + 1);
                    if (strlen($afterComma) === 3 && preg_match('/^\d{3}$/', $afterComma)) {
                        $value = str_replace(',', '', $value);
                    } else {
                        $value = str_replace(',', '.', $value);
                    }
                }
            }

            if (str_contains($value, '.')) {
                if (substr_count($value, '.') > 1) {
                    // Nhiều dấu chấm: 1.500.000 -> Xoá chấm
                    $value = str_replace('.', '', $value);
                }
            }
        }

        // Giữ lại số, dấu chấm và dấu trừ
        $value = preg_replace('/[^\d.-]/', '', $value);

        return is_numeric($value) ? (float) $value : null;
    }

    public static function legalVideoCategories(): array
    {
        return [
            'project_legal' => 'Pháp lý dự án',
            'contract' => 'Hợp đồng',
            'planning' => 'Quy hoạch',
            'transaction_process' => 'Quy trình giao dịch',
            'tax' => 'Thuế phí',
            'investment' => 'Đầu tư',
            'legal' => 'Pháp lý',
            'other' => 'Khác',
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
