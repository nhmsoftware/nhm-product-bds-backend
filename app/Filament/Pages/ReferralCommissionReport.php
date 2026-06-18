<?php

namespace App\Filament\Pages;

use App\Modules\EmployeeReferral\Models\ReferralCommission;
use App\Modules\EmployeeReferral\Models\Enums\CommissionPaymentStatus;
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

class ReferralCommissionReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';
    protected static ?string $navigationGroup = 'Giới thiệu';
    protected static ?string $navigationLabel = 'Báo cáo hoa hồng';
    protected static ?string $modelLabel = 'Báo cáo hoa hồng';
    protected static ?string $title = 'Báo cáo hoa hồng giới thiệu';
    protected static string $view = 'filament.pages.referral-commission-report';

    public ?array $data = [];

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
            'end_date' => now()->endOfMonth()->format('Y-m-d'),
            // Director tự động lọc theo branch_id của bản thân
            'branch_id' => $user->role === UserRole::DIRECTOR ? $user->branch_id : null,
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
                Select::make('referrer_id')
                    ->label('Nhân viên giới thiệu')
                    ->options(function () {
                        $currentUser = auth()->user();
                        if (!$currentUser) return [];

                        $query = User::query()
                            ->where('is_active', true)
                            ->where('role', UserRole::EMPLOYEE->value)
                            ->whereNotNull('job_position_id');

                        if ($currentUser->role === UserRole::DIRECTOR && $currentUser->branch_id) {
                            $query->where('branch_id', $currentUser->branch_id);
                        }

                        if ($currentUser->role === UserRole::MANAGER && $currentUser->department_id) {
                            $query->where('department_id', $currentUser->department_id);
                        }

                        return $query->pluck('name', 'id');
                    })
                    ->placeholder('Tất cả nhân viên')
                    ->searchable()
                    ->native(false),
                Select::make('branch_id')
                    ->label('Chi nhánh')
                    ->options(function () {
                        return \App\Modules\Branch\Models\Branch::where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    })
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
    }

    public function getReportData(): array
    {
        if ($this->getErrorBag()->any()) {
            return [
                'total_commission' => 0,
                'total_paid' => 0,
                'total_unpaid' => 0,
                'by_referrer' => [],
                'details' => []
            ];
        }

        $startDate = $this->data['start_date'] ?? null;
        $endDate = $this->data['end_date'] ?? null;
        $referrerId = $this->data['referrer_id'] ?? null;
        $branchId = $this->data['branch_id'] ?? null;

        $user = Filament::auth()->user();

        $query = ReferralCommission::query()
            ->select([
                'referral_commissions.id',
                'referral_commissions.amount',
                'referral_commissions.status',
                'referral_commissions.created_at',
                'users.name as referrer_name',
                'users.staff_code as referrer_code',
                'branches.name as referrer_area',
                'departments.name as referrer_dept',
                'ref_hist.name as referee_name'
            ])
            ->join('users', 'referral_commissions.referrer_id', '=', 'users.id')
            ->join('referral_histories as ref_hist', 'referral_commissions.referral_history_id', '=', 'ref_hist.id')
            ->leftJoin('departments', 'departments.id', '=', 'users.department_id')
            ->leftJoin('branches', 'branches.id', '=', 'users.branch_id');

        // Director chỉ được xem báo cáo chi nhánh của mình
        if ($user->role === UserRole::DIRECTOR && $user->branch_id) {
            $query->where('users.branch_id', $user->branch_id);
        }

        if ($startDate) {
            $query->whereDate('referral_commissions.created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('referral_commissions.created_at', '<=', $endDate);
        }
        if ($referrerId) {
            $query->where('referral_commissions.referrer_id', $referrerId);
        }
        // Filter theo chi nhánh (chỉ SUPER_ADMIN/CEO mới dùng được filter này)
        if ($branchId && $user->role !== UserRole::DIRECTOR) {
            $query->where('users.branch_id', $branchId);
        }

        $commissions = $query->get();

        $totalPaid = (int) $commissions->where('status', CommissionPaymentStatus::PAID->value)->sum('amount');
        $totalUnpaid = (int) $commissions->where('status', CommissionPaymentStatus::PENDING->value)->sum('amount');
        $totalCommission = $totalPaid + $totalUnpaid;

        // Group by referrer
        $byReferrer = $commissions->groupBy('referrer_name')->map(function ($group, $name) {
            return [
                'referrer_name' => $name,
                'referrer_code' => $group->first()->referrer_code ?: '-',
                'total_amount' => (int) $group->sum('amount'),
                'paid_amount' => (int) $group->where('status', CommissionPaymentStatus::PAID->value)->sum('amount'),
                'unpaid_amount' => (int) $group->where('status', CommissionPaymentStatus::PENDING->value)->sum('amount'),
                'count' => $group->count()
            ];
        })->sortByDesc('total_amount')->values()->toArray();

        return [
            'total_commission' => $totalCommission,
            'total_paid' => $totalPaid,
            'total_unpaid' => $totalUnpaid,
            'by_referrer' => $byReferrer,
            'details' => $commissions->sortByDesc('created_at')->take(50)->toArray()
        ];
    }
}
