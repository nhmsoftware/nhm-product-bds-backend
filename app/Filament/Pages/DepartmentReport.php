<?php

namespace App\Filament\Pages;

use App\Modules\Auth\Models\User;
use App\Modules\Auth\Models\Enums\UserRole;
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

class DepartmentReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Báo cáo';
    protected static ?string $navigationLabel = 'Báo cáo phòng ban';
    protected static ?string $modelLabel = 'Báo cáo phòng ban';
    protected static ?string $title = 'Báo cáo & Xếp hạng phòng ban';
    protected static string $view = 'filament.pages.department-report';

    public ?array $data = [];

    /** Dữ liệu drill-down phòng ban đang được chọn */
    public ?array $selectedDepartmentDetail = null;

    public static function shouldRegisterNavigation(): bool
    {
        $user = Filament::auth()->user();
        if (!$user) return false;
        return in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::CEO, UserRole::DIRECTOR]);
    }

    public function mount(): void
    {
        $user = Filament::auth()->user();
        if (!$user || !in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::CEO, UserRole::DIRECTOR])) {
            abort(403);
        }

        $this->form->fill([
            'start_date' => now()->startOfMonth()->format('Y-m-d'),
            'end_date'   => now()->endOfMonth()->format('Y-m-d'),
            'area'       => $user->role === UserRole::DIRECTOR ? $user->area : null,
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
                Select::make('area')
                    ->label('Chi nhánh / Khu vực')
                    ->options(AdminOptions::areas())
                    ->placeholder('Tất cả chi nhánh')
                    ->disabled($user && $user->role === UserRole::DIRECTOR)
                    ->native(false),
            ])
            ->statePath('data')
            ->columns(4);
    }

    public function applyFilters(): void
    {
        $this->form->getState();
        // Reset drill-down khi đổi bộ lọc
        $this->selectedDepartmentDetail = null;
    }

    /**
     * Lấy doanh thu từng user_id theo bộ lọc (1 query duy nhất)
     */
    private function fetchRevenueByUser(array $userIds, ?string $startDate, ?string $endDate): \Illuminate\Support\Collection
    {
        if (empty($userIds)) {
            return collect();
        }

        return DB::table('lot_deposit_requests')
            ->join('lots', 'lots.id', '=', 'lot_deposit_requests.lot_id')
            ->whereIn('lot_deposit_requests.user_id', $userIds)
            ->whereIn('lot_deposit_requests.status', [2, 4])
            ->whereNull('lot_deposit_requests.deleted_at')
            ->whereNull('lots.deleted_at')
            ->when($startDate, fn ($q) => $q->whereDate('lot_deposit_requests.created_at', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->whereDate('lot_deposit_requests.created_at', '<=', $endDate))
            ->selectRaw('lot_deposit_requests.user_id, COALESCE(SUM(lots.price), 0) as total_revenue')
            ->groupBy('lot_deposit_requests.user_id')
            ->pluck('total_revenue', 'user_id');
    }

    /**
     * Drill-down: Lấy chi tiết danh sách nhân viên trong một phòng ban.
     */
    public function selectDepartment(string $departmentName): void
    {
        $user = Filament::auth()->user();
        if (!$user) return;

        try {
            $filter = $this->form->getState();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->selectedDepartmentDetail = null;
            return;
        }

        $startDate = $filter['start_date'] ?? null;
        $endDate   = $filter['end_date'] ?? null;
        $area      = $filter['area'] ?? null;

        $empQuery = User::query()
            ->where('role', UserRole::EMPLOYEE->value)
            ->where('is_active', true)
            ->whereIn('department_id', function ($q) use ($departmentName) {
                $q->select('id')->from('departments')->where('name', $departmentName);
            })
            ->whereNotNull('job_position_id');

        if ($user->role === UserRole::DIRECTOR) {
            $empQuery->where('area', $user->area);
        } elseif ($area) {
            $empQuery->where('area', $area);
        }

        $employees = $empQuery
            ->withCount([
                'lotDepositRequests as successful_transactions' => function ($q) use ($startDate, $endDate) {
                    $q->whereIn('status', [2, 4]);
                    if ($startDate) $q->whereDate('created_at', '>=', $startDate);
                    if ($endDate) $q->whereDate('created_at', '<=', $endDate);
                },
                'siteTours as site_tours_count' => function ($q) use ($startDate, $endDate) {
                    if ($startDate) $q->whereDate('created_at', '>=', $startDate);
                    if ($endDate) $q->whereDate('created_at', '<=', $endDate);
                },
                'customerMeetings as customer_meetings_count' => function ($q) use ($startDate, $endDate) {
                    if ($startDate) $q->whereDate('created_at', '>=', $startDate);
                    if ($endDate) $q->whereDate('created_at', '<=', $endDate);
                },
                'referrals as referrals_count' => function ($q) use ($startDate, $endDate) {
                    $q->where('referral_type', 1)->where('status', 2);
                    if ($startDate) $q->whereDate('created_at', '>=', $startDate);
                    if ($endDate) $q->whereDate('created_at', '<=', $endDate);
                },
                'attendances as working_days' => function ($q) use ($startDate, $endDate) {
                    $q->whereIn('status', [1, 2]);
                    if ($startDate) $q->whereDate('work_date', '>=', $startDate);
                    if ($endDate) $q->whereDate('work_date', '<=', $endDate);
                },
                'attendances as absences' => function ($q) use ($startDate, $endDate) {
                    $q->where('status', 3);
                    if ($startDate) $q->whereDate('work_date', '>=', $startDate);
                    if ($endDate) $q->whereDate('work_date', '<=', $endDate);
                },
            ])
            ->get();

        // Lấy doanh thu từng nhân viên (1 query)
        $revenueByUser = $this->fetchRevenueByUser($employees->pluck('id')->toArray(), $startDate, $endDate);

        $employeeList = $employees->map(function ($emp) use ($revenueByUser) {
            $revenue = (int) ($revenueByUser[$emp->id] ?? 0);

            return [
                'name'                    => $emp->name,
                'staff_code'              => $emp->staff_code ?: '-',
                'job_position'            => $emp->job_position ?: '-',
                'revenue'                 => $revenue,
                'successful_transactions' => (int) $emp->successful_transactions,
                'site_tours'              => (int) $emp->site_tours_count,
                'customer_meetings'       => (int) $emp->customer_meetings_count,
                'referrals'               => (int) $emp->referrals_count,
                'working_days'            => (int) $emp->working_days,
                'absences'                => (int) $emp->absences,
            ];
        })->sortByDesc('revenue')->values()->toArray();

        $totalRevenue = array_sum(array_column($employeeList, 'revenue'));

        $this->selectedDepartmentDetail = [
            'department_name'         => $departmentName,
            'total_employees'         => $employees->count(),
            'total_revenue'           => $totalRevenue,
            'employees'               => $employeeList,
        ];
    }

    public function closeDepartmentDetail(): void
    {
        $this->selectedDepartmentDetail = null;
    }

    public function getReportData(): array
    {
        if ($this->getErrorBag()->any()) {
            return [];
        }

        $startDate  = $this->data['start_date'] ?? null;
        $endDate    = $this->data['end_date'] ?? null;
        $department = $this->data['department'] ?? null;
        $area       = $this->data['area'] ?? null;

        $user = Filament::auth()->user();

        $query = User::query()
            ->where('role', UserRole::EMPLOYEE->value)
            ->where('is_active', true)
            ->whereNotNull('department_id')
            ->whereNotNull('job_position_id');

        if ($user->role === UserRole::DIRECTOR) {
            $query->where('area', $user->area);
        }

        if ($department) {
            $query->whereIn('department_id', function ($q) use ($department) {
                $q->select('id')->from('departments')->where('name', $department);
            });
        }
        if ($area) {
            $query->where('area', $area);
        }

        $users = $query->withCount([
                'lotDepositRequests as successful_transactions' => function ($q) use ($startDate, $endDate) {
                    $q->whereIn('status', [2, 4]);
                    if ($startDate) $q->whereDate('created_at', '>=', $startDate);
                    if ($endDate) $q->whereDate('created_at', '<=', $endDate);
                },
                'siteTours as site_tours_count' => function ($q) use ($startDate, $endDate) {
                    if ($startDate) $q->whereDate('created_at', '>=', $startDate);
                    if ($endDate) $q->whereDate('created_at', '<=', $endDate);
                },
                'customerMeetings as customer_meetings_count' => function ($q) use ($startDate, $endDate) {
                    if ($startDate) $q->whereDate('created_at', '>=', $startDate);
                    if ($endDate) $q->whereDate('created_at', '<=', $endDate);
                },
                'referrals as referrals_count' => function ($q) use ($startDate, $endDate) {
                    $q->where('referral_type', 1)->where('status', 2);
                    if ($startDate) $q->whereDate('created_at', '>=', $startDate);
                    if ($endDate) $q->whereDate('created_at', '<=', $endDate);
                },
                'attendances as working_days' => function ($q) use ($startDate, $endDate) {
                    $q->whereIn('status', [1, 2]);
                    if ($startDate) $q->whereDate('work_date', '>=', $startDate);
                    if ($endDate) $q->whereDate('work_date', '<=', $endDate);
                },
                'attendances as fixed_schedule_absences' => function ($q) use ($startDate, $endDate) {
                    $q->where('status', 3);
                    if ($startDate) $q->whereDate('work_date', '>=', $startDate);
                    if ($endDate) $q->whereDate('work_date', '<=', $endDate);
                },
            ])
            ->get();

        // Lấy doanh thu tất cả nhân viên (1 query duy nhất)
        $revenueByUser = $this->fetchRevenueByUser($users->pluck('id')->toArray(), $startDate, $endDate);

        $departmentsReport = $users->groupBy('department')->map(function ($usersInDept, $deptName) use ($revenueByUser) {
            $totalRevenue = $usersInDept->sum(fn ($u) => (int) ($revenueByUser[$u->id] ?? 0));

            return [
                'department_name'         => $deptName ?: '-',
                'total_employees'         => $usersInDept->count(),
                'total_revenue'           => $totalRevenue,
                'successful_transactions' => (int) $usersInDept->sum('successful_transactions'),
                'site_tours'              => (int) $usersInDept->sum('site_tours_count'),
                'customer_meetings'       => (int) $usersInDept->sum('customer_meetings_count'),
                'referrals'               => (int) $usersInDept->sum('referrals_count'),
                'working_days'            => (int) $usersInDept->sum('working_days'),
                'fixed_schedule_absences' => (int) $usersInDept->sum('fixed_schedule_absences'),
            ];
        // Xếp hạng theo DOANH THU (tổng giá trị lô đất giao dịch thành công)
        })->sortByDesc('total_revenue')->values()->toArray();

        return $departmentsReport;
    }
}
