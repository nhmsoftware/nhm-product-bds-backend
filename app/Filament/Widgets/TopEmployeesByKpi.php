<?php

namespace App\Filament\Widgets;

use App\Modules\Auth\Models\EmployeeProfile;
use App\Modules\Auth\Models\Enums\UserRole;
use Filament\Facades\Filament;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class TopEmployeesByKpi extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Top nhân viên KPI';
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => EmployeeProfile::query()
                ->with('user')
                ->whereHas('user', fn (Builder $query) => $query
                    ->whereNull('deleted_at')

                    ->when($this->scopeArea(), function (Builder $query, string $area) {
                        if (\Illuminate\Support\Str::isUuid($area)) {
                            return $query->where('branch_id', $area);
                        }
                        return $query->whereIn('branch_id', function ($qb) use ($area) {
                            $qb->select('id')->from('branches')->where('name', $area);
                        });
                    }))
                ->orderByDesc('kpi_stars')
                ->orderByDesc('reward_points')
                ->limit(5))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Nhân viên'),
                Tables\Columns\TextColumn::make('user.departmentRel.name')->label('Phòng ban')->placeholder('Chưa cập nhật'),
                Tables\Columns\TextColumn::make('kpi_stars')->label('Tổng KPI')->numeric(),
                Tables\Columns\TextColumn::make('reward_points')->label('Điểm thưởng')->numeric(),
            ])
            ->paginated(false);
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
}
