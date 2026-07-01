<?php

namespace App\Filament\Widgets;

use App\Modules\Area\Models\Enums\LotDepositRequestStatus;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

use Filament\Support\RawJs;

class BranchRevenueChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Doanh thu theo chi nhánh';
    protected static ?string $description = 'So sánh doanh thu, chi phí ước tính và lợi nhuận ước tính của từng chi nhánh.';
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $rows = $this->branchMetricsQuery()->get();

        return [
            'datasets' => [
                [
                    'label' => 'Doanh thu',
                    'data' => $rows->pluck('revenue')->map(fn ($value): float => round(((int) $value) / 1_000_000_000, 2))->all(),
                    'backgroundColor' => '#3b82f6',
                    'borderColor' => '#2563eb',
                ],
                [
                    'label' => 'Chi phí ước tính',
                    'data' => $rows->pluck('estimated_cost')->map(fn ($value): float => round(((int) $value) / 1_000_000_000, 2))->all(),
                    'backgroundColor' => '#22c55e',
                    'borderColor' => '#16a34a',
                ],
                [
                    'label' => 'Lợi nhuận ước tính',
                    'data' => $rows->pluck('estimated_profit')->map(fn ($value): float => round(((int) $value) / 1_000_000_000, 2))->all(),
                    'backgroundColor' => '#fb923c',
                    'borderColor' => '#f97316',
                ],
            ],
            'labels' => $rows->pluck('branch_name')->all(),
        ];
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<JS
            {
                scales: {
                    y: {
                        ticks: {
                            callback: function(value) {
                                return value + ' tỷ';
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y + ' tỷ';
                            }
                        }
                    }
                }
            }
        JS);
    }

    protected function getType(): string
    {
        return 'bar';
    }

    private function branchMetricsQuery(): Builder
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

        return DB::table('branches')
            ->leftJoinSub($transactionSubquery, 'branch_transactions', function ($join): void {
                $join->on('branch_transactions.branch_id', '=', 'branches.id');
            })
            ->where('branches.is_active', true)
            ->when($this->scopeBranch(), function (Builder $query, string $branch) {
                if (\Illuminate\Support\Str::isUuid($branch)) {
                    return $query->where('branches.id', $branch);
                }
                return $query->where('branches.name', $branch);
            })
            ->selectRaw('branches.name as branch_name')
            ->selectRaw('COALESCE(branch_transactions.completed_transactions, 0) as completed_transactions')
            ->selectRaw('COALESCE(branch_transactions.revenue, 0) as revenue')
            ->selectRaw('ROUND(COALESCE(branch_transactions.revenue, 0) * 0.65) as estimated_cost')
            ->selectRaw('ROUND(COALESCE(branch_transactions.revenue, 0) * 0.35) as estimated_profit')
            ->orderBy('branches.sort')
            ->orderBy('branches.name');
    }

    private function applyDateFilters(Builder $query, string $column): void
    {
        $year = $this->filterInt('year');
        $month = $this->filterInt('month');
        $quarter = $this->filterInt('quarter');

        $query
            ->when($year, fn (Builder $query, int $year) => $query->whereYear($column, $year))
            ->when($month, fn (Builder $query, int $month) => $query->whereMonth($column, $month))
            ->when(!$month && $quarter, function (Builder $query) use ($quarter, $column): void {
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
