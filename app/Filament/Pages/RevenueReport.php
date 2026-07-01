<?php

namespace App\Filament\Pages;

use App\Modules\Area\Models\LotDepositRequest;
use App\Modules\Area\Models\Enums\LotDepositRequestStatus;
use App\Modules\Area\Models\Area;
use App\Filament\Support\AdminOptions;
use Filament\Pages\Page;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RevenueReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationGroup = 'Báo cáo';
    protected static ?string $navigationLabel = 'Báo cáo doanh thu';
    protected static ?string $modelLabel = 'Báo cáo doanh thu';
    protected static ?string $title = 'Báo cáo doanh thu';
    protected static string $view = 'filament.pages.revenue-report';

    public ?array $data = [];
    public array $chartData = [];

    public static function shouldRegisterNavigation(): bool
    {
        $user = Filament::auth()->user();
        if (!$user) return false;
        return $user->hasAnyPermission(['manage_all', 'manage_branch']);
    }

    public function mount(): void
    {
        $user = Filament::auth()->user();
        if (!$user || !$user->hasAnyPermission(['manage_all', 'manage_branch'])) {
            abort(403);
        }

        $this->form->fill([
            'start_date' => now()->startOfMonth()->format('Y-m-d'),
            'end_date' => now()->endOfMonth()->format('Y-m-d'),
            'area' => $user->role?->name === 'gdkd' ? $user->area : null,
        ]);
    }

    public function form(Form $form): Form
    {
        $user = Filament::auth()->user();
        return $form
            ->schema([
                DatePicker::make('start_date')
                    ->label('Từ ngày')
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->beforeOrEqual('end_date')
                    ->validationMessages([
                        'before_or_equal' => 'Từ ngày không được sau Đến ngày.',
                    ]),
                DatePicker::make('end_date')
                    ->label('Đến ngày')
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->afterOrEqual('start_date')
                    ->validationMessages([
                        'after_or_equal' => 'Đến ngày không được trước Từ ngày.',
                    ]),
                Select::make('department')
                    ->label('Phòng ban')
                    ->options(AdminOptions::departments())
                    ->placeholder('Tất cả phòng ban')
                    ->native(false),
                Select::make('project_id')
                    ->label('Dự án / Khu đất')
                    ->options(Area::query()->pluck('name', 'id'))
                    ->placeholder('Tất cả dự án')
                    ->searchable()
                    ->native(false),
                Select::make('area')
                    ->label('Chi nhánh / Khu vực')
                    ->options(AdminOptions::areas())
                    ->placeholder('Tất cả chi nhánh')
                    ->disabled($user && $user->role?->name === 'gdkd')
                    ->native(false),
            ])
            ->statePath('data')
            ->columns(5);
    }

    public function applyFilters(): void
    {
        $this->form->getState();
    }

    public function getReportData(): array
    {
        if ($this->getErrorBag()->any()) {
            return [
                'total_revenue' => 0,
                'total_transactions' => 0,
                'by_department' => [],
                'by_project' => [],
                'by_employee' => [],
                'transactions' => [],
                'chart_data' => [
                    'by_month' => ['labels' => [], 'values' => []],
                    'by_quarter' => ['labels' => [], 'values' => []],
                    'by_year' => ['labels' => [], 'values' => []],
                ]
            ];
        }

        $startDate = $this->data['start_date'] ?? null;
        $endDate = $this->data['end_date'] ?? null;
        $department = $this->data['department'] ?? null;
        $projectId = $this->data['project_id'] ?? null;
        $area = $this->data['area'] ?? null;

        $query = LotDepositRequest::query()
            ->select([
                'lot_deposit_requests.id',
                'lot_deposit_requests.created_at',
                'lots.price',
                'departments.name as department',
                'branches.name as user_area',
                'users.name as user_name',
                'users.id as user_id',
                'areas.name as project_name',
                'areas.id as project_id',
                'lots.code as lot_code'
            ])
            ->join('lots', 'lot_deposit_requests.lot_id', '=', 'lots.id')
            ->join('areas', 'lots.area_id', '=', 'areas.id')
            ->join('users', 'lot_deposit_requests.user_id', '=', 'users.id')
            ->leftJoin('departments', 'departments.id', '=', 'users.department_id')
            ->leftJoin('branches', 'branches.id', '=', 'users.branch_id')
            ->whereIn('lot_deposit_requests.status', [LotDepositRequestStatus::APPROVED->value, LotDepositRequestStatus::COMPLETED->value])
            ->where('users.is_active', true)
            ->where(fn ($q) => $q->whereNotIn('users.role_id', fn($sub) => $sub->select('id')->from('roles')->where('name', 'employee'))->orWhereNotNull('users.job_position_id'))
            ->whereNull('lot_deposit_requests.deleted_at');

        if ($startDate) {
            $query->whereDate('lot_deposit_requests.created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('lot_deposit_requests.created_at', '<=', $endDate);
        }
        if ($department) {
            $query->where('departments.name', $department);
        }
        if ($projectId) {
            $query->where('areas.id', $projectId);
        }
        if ($area) {
            $query->where('branches.name', $area);
        }

        $transactions = $query->get();

        // 1. Overview
        $totalRevenue = (int) $transactions->sum('price');
        $totalTransactions = $transactions->count();

        // 2. By Department
        $byDepartment = $transactions->groupBy('department')->map(function ($group, $dept) {
            return [
                'department_name' => $dept ?: 'Không xác định',
                'revenue' => (int) $group->sum('price'),
                'transactions_count' => $group->count()
            ];
        })->sortByDesc('revenue')->values()->toArray();

        // 3. By Project
        $byProject = $transactions->groupBy('project_name')->map(function ($group, $name) {
            return [
                'project_name' => $name ?: 'Không xác định',
                'revenue' => (int) $group->sum('price'),
                'transactions_count' => $group->count()
            ];
        })->sortByDesc('revenue')->values()->toArray();

        // 4. By Employee
        $byEmployee = $transactions->groupBy('user_id')->map(function ($group) {
            return [
                'user_name' => $group->first()->user_name,
                'revenue' => (int) $group->sum('price'),
                'transactions_count' => $group->count()
            ];
        })->sortByDesc('revenue')->values()->toArray();

        // 5. Chart Data: Group by Month, Quarter, Year
        $byMonth = [];
        $byQuarter = [
            'Q1' => 0,
            'Q2' => 0,
            'Q3' => 0,
            'Q4' => 0,
        ];
        $byYear = [];

        foreach ($transactions as $tx) {
            $date = Carbon::parse($tx->created_at);

            // Month key: MM/YYYY
            $monthKey = $date->format('m/Y');
            if (!isset($byMonth[$monthKey])) {
                $byMonth[$monthKey] = 0;
            }
            $byMonth[$monthKey] += (int) $tx->price;

            // Quarter key: Q1, Q2, Q3, Q4
            $quarterKey = 'Q' . ceil($date->month / 3);
            $byQuarter[$quarterKey] += (int) $tx->price;

            // Year key: YYYY
            $yearKey = $date->format('Y');
            if (!isset($byYear[$yearKey])) {
                $byYear[$yearKey] = 0;
            }
            $byYear[$yearKey] += (int) $tx->price;
        }

        // Sort Month keys
        uksort($byMonth, function($a, $b) {
            $aParts = explode('/', $a);
            $bParts = explode('/', $b);
            return ($aParts[1] <=> $bParts[1]) ?: ($aParts[0] <=> $bParts[0]);
        });
        ksort($byYear);

        $this->chartData = [
            'by_month' => [
                'labels' => array_keys($byMonth),
                'values' => array_values($byMonth),
            ],
            'by_quarter' => [
                'labels' => array_keys($byQuarter),
                'values' => array_values($byQuarter),
            ],
            'by_year' => [
                'labels' => array_keys($byYear),
                'values' => array_values($byYear),
            ],
        ];

        return [
            'total_revenue' => $totalRevenue,
            'total_transactions' => $totalTransactions,
            'by_department' => $byDepartment,
            'by_project' => $byProject,
            'by_employee' => $byEmployee,
            'transactions' => $transactions->sortByDesc('created_at')->take(50)->toArray(),
            'chart_data' => $this->chartData
        ];
    }
}
