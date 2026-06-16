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
            ->where('lot_deposit_requests.status', LotDepositRequestStatus::COMPLETED->value)
            ->whereNull('lot_deposit_requests.deleted_at')
            ->whereNull('transaction_users.deleted_at')
            ->whereNull('lots.deleted_at')
            ->when($this->scopeArea(), function ($query, string $area): void {
                $query->join('areas', 'areas.id', '=', 'lots.area_id')
                    ->where('areas.name', $area)
                    ->whereNull('areas.deleted_at');
            });

        $this->applyDateFilters($transactionSubquery, 'lot_deposit_requests.created_at');

        $transactionSubquery
            ->selectRaw('transaction_users.department as department_name')
            ->selectRaw('COUNT(*) as successful_transactions')
            ->selectRaw('COALESCE(SUM(lots.price), 0) as total_revenue')
            ->groupBy('transaction_users.department');

        return DB::table('users')
            ->leftJoin('employee_profiles', 'employee_profiles.user_id', '=', 'users.id')
            ->leftJoinSub($transactionSubquery, 'department_transactions', function ($join): void {
                $join->on('department_transactions.department_name', '=', 'users.department');
            })
            ->whereNotNull('users.department')
            ->where('users.is_active', true)
            ->whereNull('users.deleted_at')
            ->when($this->scopeArea(), fn ($query, string $area) => $query->where('users.area', $area))
            ->selectRaw('MIN(users.id) as id')
            ->selectRaw('users.department as department_name')
            ->selectRaw('COALESCE(SUM(employee_profiles.kpi_stars), 0) as total_kpi')
            ->selectRaw('COALESCE(MAX(department_transactions.successful_transactions), 0) as successful_transactions')
            ->selectRaw('COALESCE(MAX(department_transactions.total_revenue), 0) as total_revenue')
            ->groupBy('users.department');
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
            return filled($user->area) ? (string) $user->area : '__director_without_area__';
        }

        return $this->filterValue('area');
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
