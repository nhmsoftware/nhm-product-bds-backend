<?php

namespace App\Filament\Widgets;

use Filament\Facades\Filament;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TopDepartmentsByKpi extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Top phòng ban';
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => TopDepartmentRecord::query()
                ->fromSub($this->topDepartmentsQuery(), 'top_department_records')
                ->orderByDesc('total_kpi')
                ->limit(5))
            ->columns([
                Tables\Columns\TextColumn::make('department_name')->label('Phòng ban')->placeholder('Chưa cập nhật'),
                Tables\Columns\TextColumn::make('employee_count')->label('Nhân sự')->numeric(),
                Tables\Columns\TextColumn::make('total_kpi')->label('Tổng KPI')->numeric(),
            ])
            ->paginated(false);
    }

    private function topDepartmentsQuery(): \Illuminate\Database\Query\Builder
    {
        return DB::table('users')
            ->leftJoin('employee_profiles', 'employee_profiles.user_id', '=', 'users.id')
            ->join('departments', 'departments.id', '=', 'users.department_id')
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
            ->selectRaw('COUNT(users.id) as employee_count')
            ->selectRaw('COALESCE(SUM(' . \App\Modules\Auth\Models\EmployeeProfile::getKpiPointsSelectRaw('users.id') . '), 0) as total_kpi')
            ->groupBy('users.department_id', 'departments.name');
    }

    private function scopeArea(): ?string
    {
        $user = Filament::auth()->user();

        if ($user->role?->name === 'gdkd') {
            return filled($user->branch_id) ? (string) $user->branch_id : '__director_without_area__';
        }

        return $this->filterValue('branch');
    }

    private function filterValue(string $key): ?string
    {
        $value = $this->filters[$key] ?? null;

        return filled($value) ? (string) $value : null;
    }
}

class TopDepartmentRecord extends \Illuminate\Database\Eloquent\Model
{
    public $timestamps = false;
    protected $guarded = [];
}
