<?php

namespace App\Filament\Widgets;

use App\Modules\Auth\Models\Enums\UserRole;
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
            ->whereNotNull('users.department')
            ->where('users.is_active', true)
            ->whereNull('users.deleted_at')
            ->when($this->scopeArea(), fn ($query, string $area) => $query->where('users.area', $area))
            ->selectRaw('MIN(users.id) as id')
            ->selectRaw('users.department as department_name')
            ->selectRaw('COUNT(users.id) as employee_count')
            ->selectRaw('COALESCE(SUM(employee_profiles.kpi_stars), 0) as total_kpi')
            ->groupBy('users.department');
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
}

class TopDepartmentRecord extends \Illuminate\Database\Eloquent\Model
{
    public $timestamps = false;
    protected $guarded = [];
}
