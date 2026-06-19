<?php

namespace App\Filament\Widgets;

use App\Modules\Area\Models\Enums\LotDepositRequestStatus;
use App\Modules\Auth\Models\Enums\UserRole;
use Filament\Facades\Filament;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DepartmentPerformance extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Hiệu suất theo phòng ban';
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => DepartmentPerformanceRecord::query()
                ->fromSub($this->departmentStatsQuery(), 'department_performance_records')
                ->orderByDesc('total_kpi'))
            ->columns([
                Tables\Columns\TextColumn::make('department_name')->label('Tên phòng ban')->placeholder('Chưa cập nhật'),
                Tables\Columns\TextColumn::make('total_kpi')->label('Tổng KPI')->numeric(),
                Tables\Columns\TextColumn::make('successful_transactions')->label('Giao dịch thành công')->numeric(),
                Tables\Columns\TextColumn::make('total_revenue')->label('Tổng doanh thu')->money('VND', divideBy: 1),
            ])
            ->paginated(false);
    }

    private function departmentStatsQuery(): \Illuminate\Database\Query\Builder
    {
        $transactionSubquery = DB::table('lot_deposit_requests')
            ->join('users as transaction_users', 'transaction_users.id', '=', 'lot_deposit_requests.user_id')
            ->join('lots', 'lots.id', '=', 'lot_deposit_requests.lot_id')
            ->whereIn('lot_deposit_requests.status', [
                LotDepositRequestStatus::APPROVED->value,
                LotDepositRequestStatus::COMPLETED->value,
            ])
            ->whereNull('lot_deposit_requests.deleted_at')
            ->whereNull('transaction_users.deleted_at')
            ->whereNotNull('transaction_users.department_id')
            ->whereNull('lots.deleted_at')
            ->when($this->scopeArea(), function ($query, string $area): void {
                $query->join('areas', 'areas.id', '=', 'lots.area_id');
                if (\Illuminate\Support\Str::isUuid($area)) {
                    $query->where('areas.branch_id', $area);
                } else {
                    $query->join('branches as area_branches', 'area_branches.id', '=', 'areas.branch_id')
                        ->where('area_branches.name', $area);
                }
                $query->whereNull('areas.deleted_at');
            });

        $this->applyDateFilters($transactionSubquery, 'lot_deposit_requests.created_at');

        $transactionSubquery
            ->selectRaw('transaction_users.department_id as department_id')
            ->selectRaw('COUNT(*) as successful_transactions')
            ->selectRaw('COALESCE(SUM(lots.price), 0) as total_revenue')
            ->groupBy('transaction_users.department_id');

        return DB::table('users')
            ->leftJoin('employee_profiles', 'employee_profiles.user_id', '=', 'users.id')
            ->join('departments', 'departments.id', '=', 'users.department_id')
            ->leftJoinSub($transactionSubquery, 'department_transactions', function ($join): void {
                $join->on('department_transactions.department_id', '=', 'users.department_id');
            })
            ->whereNotNull('users.department_id')
            ->where('users.is_active', true)
            ->whereNull('users.deleted_at')
            ->when($this->scopeArea(), function ($query, string $area) {
                if (\Illuminate\Support\Str::isUuid($area)) {
                    return $query->where('users.branch_id', $area);
                }
                return $query->whereIn('users.branch_id', function ($qb) use ($area) {
                    $qb->select('id')->from('branches')->where('name', $area);
                });
            })
            ->selectRaw('MIN(users.id::text) as id')
            ->selectRaw('departments.name as department_name')
            ->selectRaw('COALESCE(SUM(' . \App\Modules\Auth\Models\EmployeeProfile::getKpiPointsSelectRaw('users.id') . '), 0) as total_kpi')
            ->selectRaw('COALESCE(MAX(department_transactions.successful_transactions), 0) as successful_transactions')
            ->selectRaw('COALESCE(MAX(department_transactions.total_revenue), 0) as total_revenue')
            ->groupBy('users.department_id', 'departments.name');
    }

    private function applyDateFilters(\Illuminate\Database\Query\Builder $query, string $column): void
    {
        $year = $this->filterInt('year');
        $month = $this->filterInt('month');
        $quarter = $this->filterInt('quarter');

        $query
            ->when($year, fn ($query, int $year) => $query->whereYear($column, $year))
            ->when($month, fn ($query, int $month) => $query->whereMonth($column, $month))
            ->when(!$month && $quarter, function ($query) use ($quarter, $column): void {
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
}

class DepartmentPerformanceRecord extends \Illuminate\Database\Eloquent\Model
{
    public $timestamps = false;
    protected $guarded = [];
}
