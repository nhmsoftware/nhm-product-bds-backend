<?php

namespace App\Filament\Widgets;

use App\Modules\Area\Models\Enums\LotDepositRequestStatus;
use App\Modules\Auth\Models\Enums\UserRole;
use Filament\Facades\Filament;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class CompanyStatsOverview extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Tổng quan công ty';

    protected function getStats(): array
    {
        $completedTransactions = $this->completedTransactionsQuery()->count();

        $revenue = (int) $this->completedTransactionsQuery()
            ->join('lots', 'lots.id', '=', 'lot_deposit_requests.lot_id')
            ->whereNull('lots.deleted_at')
            ->sum('lots.price');

        $employees = $this->employeeQuery()->count();
        $departments = (clone $this->employeeQuery())->whereNotNull('department_id')->distinct('department_id')->count('department_id');
        $customers = $this->customerQuery()->count();
        $referrals = $this->referralQuery()->count();
        $totalKpi = (int) $this->employeeQuery()
            ->join('employee_profiles', 'users.id', '=', 'employee_profiles.user_id')
            ->sum(\Illuminate\Support\Facades\DB::raw(\App\Modules\Auth\Models\EmployeeProfile::getKpiPointsSelectRaw('users.id')));

        return [
            Stat::make('Tổng nhân sự', number_format($employees, 0, ',', '.'))
                ->description('Nhân viên, trưởng phòng, giám đốc')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
            Stat::make('Tổng phòng ban', number_format($departments, 0, ',', '.'))
                ->description('Phòng ban đang có nhân sự')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('gray'),
            Stat::make('Giao dịch công chứng', number_format($completedTransactions, 0, ',', '.'))
                ->description('Giao dịch thành công')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Tổng doanh thu', $this->formatCurrency($revenue))
                ->description('Từ giao dịch đã công chứng')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            Stat::make('Tổng khách hàng', number_format($customers, 0, ',', '.'))
                ->description('Tài khoản khách hàng')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),
            Stat::make('Tổng referral', number_format($referrals, 0, ',', '.'))
                ->description('Referral phù hợp bộ lọc')
                ->descriptionIcon('heroicon-m-qr-code')
                ->color('warning'),
            Stat::make('Tổng điểm KPI', number_format($totalKpi, 0, ',', '.'))
                ->description('Toàn công ty hoặc khu vực được lọc')
                ->descriptionIcon('heroicon-m-star')
                ->color('danger'),
        ];
    }

    private function employeeQuery(): Builder
    {
        return DB::table('users')
            ->whereIn('role', [
                UserRole::EMPLOYEE->value,
                UserRole::MANAGER->value,
                UserRole::DIRECTOR->value,
            ])
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->where(fn (Builder $query) => $query->where('role', '!=', UserRole::EMPLOYEE->value)->orWhereNotNull('job_position_id'))
            ->when($this->scopeArea(), function (Builder $query, string $area) {
                if (\Illuminate\Support\Str::isUuid($area)) {
                    return $query->where('branch_id', $area);
                }
                return $query->whereIn('branch_id', function ($qb) use ($area) {
                    $qb->select('id')->from('branches')->where('name', $area);
                });
            });
    }

    private function customerQuery(): Builder
    {
        return $this->applyDateFilters(DB::table('users')
            ->where('role', UserRole::BUYER->value)
            ->whereNull('deleted_at')
            // No department check needed for customers as they shouldn't have departments
            ->when($this->scopeArea(), function (Builder $query, string $area) {
                if (\Illuminate\Support\Str::isUuid($area)) {
                    return $query->where('branch_id', $area);
                }
                return $query->whereIn('branch_id', function ($qb) use ($area) {
                    $qb->select('id')->from('branches')->where('name', $area);
                });
            }));
    }

    private function referralQuery(): Builder
    {
        return $this->applyDateFilters(DB::table('referral_histories')->whereNull('deleted_at'));
    }

    private function completedTransactionsQuery(): Builder
    {
        return $this->applyDateFilters(DB::table('lot_deposit_requests')
            ->whereIn('lot_deposit_requests.status', [
                LotDepositRequestStatus::APPROVED->value,
                LotDepositRequestStatus::COMPLETED->value,
            ])
            ->whereNull('lot_deposit_requests.deleted_at')
            ->when($this->scopeArea(), function (Builder $query, string $area): void {
                $query->join('lots as area_filter_lots', 'area_filter_lots.id', '=', 'lot_deposit_requests.lot_id')
                    ->join('areas as area_filter_areas', 'area_filter_areas.id', '=', 'area_filter_lots.area_id');
                
                if (\Illuminate\Support\Str::isUuid($area)) {
                    $query->where('area_filter_areas.branch_id', $area);
                } else {
                    $query->join('branches as area_filter_branches', 'area_filter_branches.id', '=', 'area_filter_areas.branch_id')
                        ->where('area_filter_branches.name', $area);
                }

                $query->whereNull('area_filter_lots.deleted_at')
                    ->whereNull('area_filter_areas.deleted_at');
            }), 'lot_deposit_requests.created_at');
    }

    private function applyDateFilters(Builder $query, string $column = 'created_at'): Builder
    {
        $year = $this->filterInt('year');
        $month = $this->filterInt('month');
        $quarter = $this->filterInt('quarter');

        return $query
            ->when($year, fn (Builder $query, int $year) => $query->whereYear($column, $year))
            ->when($month, fn (Builder $query, int $month) => $query->whereMonth($column, $month))
            ->when(!$month && $quarter, function (Builder $query) use ($quarter, $column): void {
                $query->whereMonth($column, '>=', (($quarter - 1) * 3) + 1)
                    ->whereMonth($column, '<=', $quarter * 3);
            });
    }

    private function scopeArea(): ?string
    {
        $user = Filament::auth()->user();

        if ($user?->role === UserRole::DIRECTOR) {
            return filled($user->branch_id) ? (string) $user->branch_id : '__director_without_area__';
        }

        return $this->filterValue('branch');
    }

    private function filterValue(string $key): ?string
    {
        $value = $this->filters[$key] ?? null;

        return filled($value) ? (string) $value : null;
    }

    private function filterInt(string $key): ?int
    {
        $value = $this->filters[$key] ?? null;

        return filled($value) ? (int) $value : null;
    }

    private function formatCurrency(int $amount): string
    {
        if ($amount >= 1_000_000_000) {
            return rtrim(rtrim(number_format($amount / 1_000_000_000, 1, ',', '.'), '0'), ',') . ' tỷ';
        }

        if ($amount >= 1_000_000) {
            return rtrim(rtrim(number_format($amount / 1_000_000, 1, ',', '.'), '0'), ',') . ' triệu';
        }

        return number_format($amount, 0, ',', '.') . ' đ';
    }
}
