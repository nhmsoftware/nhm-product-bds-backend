<?php

namespace App\Filament\Pages;

use App\Modules\Auth\Models\User;
use App\Filament\Support\AdminOptions;
use App\Modules\Area\Models\Enums\LotDepositRequestStatus;
use Filament\Pages\Page;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;

class EmployeeReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-trophy';
    protected static ?string $navigationGroup = 'Báo cáo';
    protected static ?string $navigationLabel = 'Bảng xếp hạng thành tích';
    protected static ?string $modelLabel = 'Bảng xếp hạng thành tích';
    protected static ?string $title = 'Bảng xếp hạng thành tích';
    protected static string $view = 'filament.pages.employee-report';

    public ?array $data = [];
    public ?array $selectedEmployeeDetails = null;

    public static function shouldRegisterNavigation(): bool
    {
        return Filament::auth()->check();
    }

    public function mount(): void
    {
        $user = Filament::auth()->user();
        if (!$user) {
            abort(403);
        }

        $this->form->fill([
            'start_date' => now()->startOfMonth()->format('Y-m-d'),
            'end_date' => now()->endOfMonth()->format('Y-m-d'),
            'department' => $user->role?->name === 'tp_kd' ? $user->department : null,
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
                    ->disabled($user && $user->role?->name === 'tp_kd')
                    ->native(false),
                Select::make('area')
                    ->label('Khu vực / Chi nhánh')
                    ->options(AdminOptions::areas())
                    ->placeholder('Tất cả khu vực')
                    ->disabled($user && $user->role?->name === 'gdkd')
                    ->native(false),
                Select::make('employee_id')
                    ->label('Nhân viên')
                    ->options(function () {
                        $currentUser = auth()->user();
                        if (!$currentUser) return [];

                        $query = User::query()
                            ->where('is_active', true)
                            ->whereHas('role', fn($q) => $q->where('name', 'employee'))
                            ->whereNotNull('job_position_id');

                        if ($currentUser->role?->name === 'gdkd' && $currentUser->branch_id) {
                            $query->where('branch_id', $currentUser->branch_id);
                        }

                        if ($currentUser->role?->name === 'tp_kd' && $currentUser->department_id) {
                            $query->where('department_id', $currentUser->department_id);
                        }

                        return $query->pluck('name', 'id');
                    })
                    ->placeholder('Tất cả nhân viên')
                    ->searchable()
                    ->native(false),
            ])
            ->statePath('data')
            ->columns(5);
    }

    public function selectEmployee(string $employeeId): void
    {
        $user = Filament::auth()->user();
        if (!$user) return;

        $emp = User::query()
            ->where('id', $employeeId)
            ->whereHas('role', fn($q) => $q->where('name', 'employee'))
            ->whereNotNull('job_position_id')
            ->first();

        if (!$emp) {
            $this->selectedEmployeeDetails = null;
            return;
        }

        // Check authorization
        $isAuthorized = false;
        if ($user->hasAnyPermission(['manage_all', 'manage_employees'])) {
            $isAuthorized = true;
        } elseif ($user->id === $employeeId) {
            $isAuthorized = true;
        } elseif ($user->role?->name === 'tp_kd' && $user->department_id && $emp->department_id === $user->department_id) {
            $isAuthorized = true;
        } elseif ($user->role?->name === 'gdkd' && $user->branch_id && $emp->branch_id === $user->branch_id) {
            $isAuthorized = true;
        }

        if (!$isAuthorized) {
            return;
        }

        try {
            $filter = $this->form->getState();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->selectedEmployeeDetails = null;
            return;
        }
        $startDate = $filter['start_date'] ?? null;
        $endDate = $filter['end_date'] ?? null;

        $depositsQuery = $emp->lotDepositRequests()->with('lot.area');
        if ($startDate) $depositsQuery->whereDate('created_at', '>=', $startDate);
        if ($endDate) $depositsQuery->whereDate('created_at', '<=', $endDate);
        $deposits = $depositsQuery->get();

        $toursQuery = $emp->siteTours()->with('project');
        if ($startDate) $toursQuery->whereDate('created_at', '>=', $startDate);
        if ($endDate) $toursQuery->whereDate('created_at', '<=', $endDate);
        $tours = $toursQuery->get();

        $meetingsQuery = $emp->customerMeetings();
        if ($startDate) $meetingsQuery->whereDate('created_at', '>=', $startDate);
        if ($endDate) $meetingsQuery->whereDate('created_at', '<=', $endDate);
        $meetings = $meetingsQuery->get();

        $referralsQuery = $emp->referrals();
        if ($startDate) $referralsQuery->whereDate('created_at', '>=', $startDate);
        if ($endDate) $referralsQuery->whereDate('created_at', '<=', $endDate);
        $referrals = $referralsQuery->get();

        $attendancesQuery = $emp->attendances();
        if ($startDate) $attendancesQuery->whereDate('work_date', '>=', $startDate);
        if ($endDate) $attendancesQuery->whereDate('work_date', '<=', $endDate);
        $attendances = $attendancesQuery->get();

        $successfulDepositsCount = $deposits->filter(function ($d) {
            $statusVal = $d->status instanceof \BackedEnum ? $d->status->value : (int)$d->status;
            return in_array($statusVal, [2, 4]);
        })->count();
        $successfulReferralsCount = $referrals->filter(function ($r) {
            $typeVal = $r->referral_type instanceof \BackedEnum ? $r->referral_type->value : (int)$r->referral_type;
            $statusVal = $r->status instanceof \BackedEnum ? $r->status->value : (int)$r->status;
            return $typeVal === 1 && $statusVal === 2;
        })->count();
        $workingDaysCount = $attendances->filter(function ($a) {
            $statusVal = $a->status instanceof \BackedEnum ? $a->status->value : (int)$a->status;
            return in_array($statusVal, [1, 2]);
        })->count();
        $absencesCount = $attendances->filter(function ($a) {
            $statusVal = $a->status instanceof \BackedEnum ? $a->status->value : (int)$a->status;
            return $statusVal === 3;
        })->count();

        $totalKpiPoints = ($successfulDepositsCount * 10)
            + ($tours->count() * 1)
            + ($meetings->count() * 0.5)
            + ($successfulReferralsCount * 1)
            + (floor($workingDaysCount / 5) * 1)
            - ($absencesCount * 0.5);

        $this->selectedEmployeeDetails = [
            'name' => $emp->name,
            'staff_code' => $emp->staff_code ?: '-',
            'department' => $emp->department ?: '-',
            'job_position' => $emp->job_position ?: '-',
            'area' => $emp->area ?: '-',
            'total_kpi_points' => $totalKpiPoints,
            'deposits' => $deposits->map(fn($d) => [
                'lot_code' => $d->lot?->code ?? '-',
                'area_name' => $d->lot?->area?->name ?? '-',
                'reason' => $d->reason ?? '-',
                'status_label' => $d->status instanceof LotDepositRequestStatus ? $d->status->label() : LotDepositRequestStatus::tryFrom((int)$d->status)?->label() ?? '-',
                'status_color' => $d->status === LotDepositRequestStatus::COMPLETED ? 'success' : ($d->status === LotDepositRequestStatus::APPROVED ? 'warning' : 'gray'),
                'created_at' => $d->created_at?->format('d/m/Y H:i') ?? '-',
            ])->toArray(),
            'tours' => $tours->map(fn($t) => [
                'customer_name' => $t->customer_name ?? '-',
                'unit_code' => $t->unit_code ?? '-',
                'area_name' => $t->project?->name ?? '-',
                'created_at' => Carbon::parse($t->created_at)->format('d/m/Y H:i'),
            ])->toArray(),
            'meetings' => $meetings->map(fn($m) => [
                'customer_name' => $m->customer_name ?? '-',
                'meeting_date' => Carbon::parse($m->meeting_date)->format('d/m/Y H:i'),
                'location' => $m->location ?? '-',
                'purpose' => $m->purpose ?? '-',
            ])->toArray(),
            'referrals' => $referrals->map(fn($r) => [
                'referred_name' => $r->referred_name ?? '-',
                'referral_type' => $r->referral_type === 1 ? 'Tuyển dụng' : 'Khách hàng',
                'status_label' => $r->status === 2 ? 'Đã duyệt' : ($r->status === 3 ? 'Từ chối' : 'Chờ duyệt'),
                'created_at' => Carbon::parse($r->created_at)->format('d/m/Y H:i'),
            ])->toArray(),
            'attendances' => $attendances->map(fn($a) => [
                'work_date' => Carbon::parse($a->work_date)->format('d/m/Y'),
                'status_label' => $a->status === 1 ? 'Đúng giờ' : ($a->status === 2 ? 'Đi muộn' : 'Vắng mặt'),
                'status_color' => $a->status === 1 ? 'success' : ($a->status === 2 ? 'warning' : 'danger'),
                'check_in' => $a->check_in ?? '-',
                'check_out' => $a->check_out ?? '-',
            ])->toArray(),
        ];
    }

    public function closeEmployeeDetail(): void
    {
        $this->selectedEmployeeDetails = null;
    }

    public function applyFilters(): void
    {
        $this->form->getState();
    }

    public function getReportData(): array
    {
        if ($this->getErrorBag()->any()) {
            return [];
        }

        $startDate = $this->data['start_date'] ?? null;
        $endDate = $this->data['end_date'] ?? null;
        $department = $this->data['department'] ?? null;
        $area = $this->data['area'] ?? null;
        $employeeId = $this->data['employee_id'] ?? null;

        $user = Filament::auth()->user();

        $query = User::query()
            ->whereHas('role', fn($q) => $q->where('name', 'employee'))
            ->where('is_active', true)
            ->whereNotNull('job_position_id');

        // Apply Role Restriction
        if ($user->role?->name === 'tp_kd') {
            $query->where('department_id', $user->department_id);
        } elseif ($user->role?->name === 'gdkd') {
            $query->where('area', $user->area);
        }

        // Apply filters
        if ($department) {
            $query->where('department_id', function ($q) use ($department) {
                $q->select('id')->from('departments')->where('name', $department);
            });
        }
        if ($area) {
            $query->where('area', $area);
        }
        if ($employeeId) {
            $query->where('id', $employeeId);
        }

        $employees = $query->with('employeeProfile')
            ->withCount([
                'lotDepositRequests as successful_transactions' => function ($q) use ($startDate, $endDate) {
                    $q->whereIn('status', [2, 4]); // APPROVED or COMPLETED
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
                    $q->where('referral_type', 1)->where('status', 2); // APPROVED
                    if ($startDate) $q->whereDate('created_at', '>=', $startDate);
                    if ($endDate) $q->whereDate('created_at', '<=', $endDate);
                },
                'attendances as working_days' => function ($q) use ($startDate, $endDate) {
                    $q->whereIn('status', [1, 2]); // PRESENT/LATE
                    if ($startDate) $q->whereDate('work_date', '>=', $startDate);
                    if ($endDate) $q->whereDate('work_date', '<=', $endDate);
                },
                'attendances as absences' => function ($q) use ($startDate, $endDate) {
                    $q->where('status', 3); // ABSENT
                    if ($startDate) $q->whereDate('work_date', '>=', $startDate);
                    if ($endDate) $q->whereDate('work_date', '<=', $endDate);
                }
            ])
            ->get();

        return $employees->map(function ($emp) {
            $totalKpiPoints = ($emp->successful_transactions * 10)
                + ($emp->site_tours_count * 1)
                + ($emp->customer_meetings_count * 0.5)
                + ($emp->referrals_count * 1)
                + (floor($emp->working_days / 5) * 1)
                - ($emp->absences * 0.5);

            return [
                'id' => $emp->id,
                'name' => $emp->name,
                'staff_code' => $emp->staff_code ?: '-',
                'department' => $emp->department ?: '-',
                'department_id' => $emp->department_id,
                'branch_id' => $emp->branch_id,
                'job_position' => $emp->job_position ?: '-',
                'area' => $emp->area ?: '-',
                'total_kpi_points' => $totalKpiPoints,
                'successful_transactions' => $emp->successful_transactions,
                'site_tours_count' => $emp->site_tours_count,
                'customer_meetings_count' => $emp->customer_meetings_count,
                'referrals_count' => $emp->referrals_count,
                'working_days' => $emp->working_days,
                'absences' => $emp->absences,
            ];
        })->sortByDesc('total_kpi_points')->values()->toArray();
    }
}
