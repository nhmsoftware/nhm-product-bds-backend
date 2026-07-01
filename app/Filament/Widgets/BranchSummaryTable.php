<?php

namespace App\Filament\Widgets;

use App\Modules\Area\Models\Enums\LotDepositRequestStatus;
use Filament\Facades\Filament;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class BranchSummaryTable extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Tổng hợp theo chi nhánh';
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => BranchSummaryRecord::query()
                ->fromSub($this->branchSummaryQuery(), 'branch_summary_records')
                ->orderByDesc('revenue'))
            ->columns([
                Tables\Columns\TextColumn::make('branch_name')->label('Chi nhánh'),
                Tables\Columns\TextColumn::make('employee_count')->label('Nhân sự')->numeric(),
                Tables\Columns\TextColumn::make('department_count')->label('Phòng ban')->numeric(),
                Tables\Columns\TextColumn::make('completed_transactions')->label('Giao dịch công chứng')->numeric(),
                Tables\Columns\TextColumn::make('revenue')->label('Doanh thu')->money('VND', divideBy: 1),
                Tables\Columns\TextColumn::make('estimated_cost')->label('Chi phí ước tính')->money('VND', divideBy: 1),
                Tables\Columns\TextColumn::make('estimated_profit')->label('Lợi nhuận ước tính')->money('VND', divideBy: 1),
                Tables\Columns\TextColumn::make('total_kpi')->label('Tổng KPI')->numeric(),
            ])
            ->paginated(false);
    }

    private function branchSummaryQuery(): \Illuminate\Database\Query\Builder
    {
        $transactionSubquery = DB::table('lot_deposit_requests')
            ->join('lots', 'lots.id', '=', 'lot_deposit_requests.lot_id')
            ->join('areas', 'areas.id', '=', 'lots.area_id')
            ->whereIn('lot_deposit_requests.status', [
                LotDepositRequestStatus::APPROVED->value,
                LotDepositRequestStatus::COMPLETED->value,
            ])
            ->whereNull('lot_deposit_requests.deleted_at')
            ->whereNull('lots.deleted_at')
            ->whereNull('areas.deleted_at');

        $this->applyDateFilters($transactionSubquery, 'lot_deposit_requests.created_at');

        $transactionSubquery
            ->selectRaw('areas.branch_id as branch_id')
            ->selectRaw('COUNT(*) as completed_transactions')
            ->selectRaw('COALESCE(SUM(lots.price), 0) as revenue')
            ->groupBy('areas.branch_id');

        $employeeSubquery = DB::table('users')
            ->leftJoin('employee_profiles', 'employee_profiles.user_id', '=', 'users.id')
            ->whereIn('users.role_id', function ($q) {
                $q->select('id')->from('roles')->whereIn('name', ['employee', 'tp_kd', 'gdkd']);
            })
            ->where('users.is_active', true)
            ->whereNull('users.deleted_at')
            ->where(fn ($query) => $query->whereIn('users.role_id', function ($q) {
                $q->select('id')->from('roles')->where('name', '!=', 'employee');
            })->orWhereNotNull('users.job_position_id'))
            ->selectRaw('users.branch_id as branch_id')
            ->selectRaw('COUNT(users.id) as employee_count')
            ->selectRaw("COUNT(DISTINCT users.department_id) as department_count")
            ->selectRaw('COALESCE(SUM(' . \App\Modules\Auth\Models\EmployeeProfile::getKpiPointsSelectRaw('users.id') . '), 0) as total_kpi')
            ->groupBy('users.branch_id');

        return DB::table('branches')
            ->leftJoinSub($transactionSubquery, 'branch_transactions', function ($join): void {
                $join->on('branch_transactions.branch_id', '=', 'branches.id');
            })
            ->leftJoinSub($employeeSubquery, 'branch_employees', function ($join): void {
                $join->on('branch_employees.branch_id', '=', 'branches.id');
            })
            ->where('branches.is_active', true)
            ->when($this->scopeBranch(), function ($query, string $branch) {
                if (\Illuminate\Support\Str::isUuid($branch)) {
                    return $query->where('branches.id', $branch);
                }
                return $query->where('branches.name', $branch);
            })
            ->selectRaw('branches.id as id')
            ->selectRaw('branches.name as branch_name')
            ->selectRaw('COALESCE(branch_employees.employee_count, 0) as employee_count')
            ->selectRaw('COALESCE(branch_employees.department_count, 0) as department_count')
            ->selectRaw('COALESCE(branch_transactions.completed_transactions, 0) as completed_transactions')
            ->selectRaw('COALESCE(branch_transactions.revenue, 0) as revenue')
            ->selectRaw('ROUND(COALESCE(branch_transactions.revenue, 0) * 0.65) as estimated_cost')
            ->selectRaw('ROUND(COALESCE(branch_transactions.revenue, 0) * 0.35) as estimated_profit')
            ->selectRaw('COALESCE(branch_employees.total_kpi, 0) as total_kpi');
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

    private function scopeBranch(): ?string
    {
        $user = Filament::auth()->user();

        if ($user->role?->name === 'gdkd') {
            return filled($user->branch_id) ? (string) $user->branch_id : '__director_without_branch__';
        }

        $value = $this->filters['branch'] ?? null;

        return filled($value) ? (string) $value : null;
    }

    private function filterInt(string $key): ?int
    {
        $value = $this->filters[$key] ?? null;

        return filled($value) ? (int) $value : null;
    }
}

class BranchSummaryRecord extends \Illuminate\Database\Eloquent\Model
{
    public $timestamps = false;
    protected $guarded = [];
}
