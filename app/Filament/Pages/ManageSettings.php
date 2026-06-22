<?php

namespace App\Filament\Pages;

use App\Modules\Area\Models\InventorySetting;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Filament\Support\GoongLocationInput;
use Filament\Facades\Filament;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class ManageSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Cấu hình';
    protected static ?string $navigationLabel = 'Cấu hình';
    protected static ?string $title = 'Cấu hình hệ thống';
    protected static ?string $slug = 'setting';
    protected static string $view = 'filament.pages.manage-settings';

    public ?array $data = [];

    public static function shouldRegisterNavigation(): bool
    {
        $user = Filament::auth()->user();
        if (!$user) return false;
        return in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::CEO]);
    }

    public function mount(): void
    {
        $user = Filament::auth()->user();
        if (!$user || !in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::CEO])) {
            abort(403);
        }

        $settings = InventorySetting::all()->pluck('value', 'key')->toArray();

        $this->form->fill([
            'lot_lock_approval_timeout' => [
                'amount' => data_get($settings, 'lot_lock_approval_timeout.amount', 24),
                'unit' => data_get($settings, 'lot_lock_approval_timeout.unit', 'hours'),
            ],
            'kpi_points_successful_transaction' => [
                'points' => data_get($settings, 'kpi_points_successful_transaction.points', 10.0),
            ],
            'kpi_points_site_tour' => [
                'points' => data_get($settings, 'kpi_points_site_tour.points', 1.0),
            ],
            'kpi_points_customer_meeting' => [
                'points' => data_get($settings, 'kpi_points_customer_meeting.points', 0.5),
            ],
            'kpi_points_successful_referral' => [
                'points' => data_get($settings, 'kpi_points_successful_referral.points', 1.0),
            ],
            'kpi_points_work_day_rate' => [
                'points' => data_get($settings, 'kpi_points_work_day_rate.points', 1.0),
                'days' => data_get($settings, 'kpi_points_work_day_rate.days', 5),
            ],
            'kpi_points_absence_penalty' => [
                'points' => data_get($settings, 'kpi_points_absence_penalty.points', 0.5),
            ],
            'attendance_no_checkout_work_day' => [
                'work_day' => (string) data_get($settings, 'attendance_no_checkout_work_day.work_day', 0.5),
            ],
            'attendance_under_6_hours_work_day' => [
                'work_day' => (string) data_get($settings, 'attendance_under_6_hours_work_day.work_day', 0.5),
            ],
            'attendance_office_address' => [
                'address' => data_get($settings, 'attendance_office_address.address', ''),
            ],
            'attendance_office_latitude' => [
                'latitude' => data_get($settings, 'attendance_office_latitude.latitude', '21.029085171034406'),
            ],
            'attendance_office_longitude' => [
                'longitude' => data_get($settings, 'attendance_office_longitude.longitude', '105.79754520314668'),
            ],
            'attendance_office_radius_meters' => [
                'radius' => data_get($settings, 'attendance_office_radius_meters.radius', 100),
            ],
            'attendance_office_wifi_ssid' => [
                'wifi_ssid' => data_get($settings, 'attendance_office_wifi_ssid.wifi_ssid', 'BDS_Office_Wifi'),
            ],
            'attendance_shift_start_time' => [
                'shift_start_time' => data_get($settings, 'attendance_shift_start_time.shift_start_time', '08:30:00'),
            ],
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Cấu hình kho hàng')
                    ->description('Thiết lập các tham số vận hành kho hàng và đặt chỗ.')
                    ->schema([
                        TextInput::make('lot_lock_approval_timeout.amount')
                            ->label('Thời gian hết hạn lock lô')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->validationMessages([
                                'required' => 'Vui lòng nhập thời gian.',
                                'min' => 'Thời gian phải lớn hơn 0.',
                            ]),
                        Select::make('lot_lock_approval_timeout.unit')
                            ->label('Đơn vị thời gian')
                            ->options([
                                'hours' => 'Giờ',
                                'days' => 'Ngày',
                            ])
                            ->required()
                            ->validationMessages([
                                'required' => 'Vui lòng chọn đơn vị.',
                            ]),
                    ])->columns(2),

                Section::make('Cấu hình quy tắc tính điểm KPI')
                    ->description('Thiết lập điểm số tự động cộng/trừ dựa trên hiệu suất của nhân viên.')
                    ->schema([
                        TextInput::make('kpi_points_successful_transaction.points')
                            ->label('Điểm / 1 giao dịch công chứng thành công')
                            ->numeric()
                            ->step(0.1)
                            ->required()
                            ->validationMessages(['required' => 'Vui lòng nhập điểm số.']),
                        TextInput::make('kpi_points_site_tour.points')
                            ->label('Điểm / 1 lượt dẫn khách đi xem')
                            ->numeric()
                            ->step(0.1)
                            ->required()
                            ->validationMessages(['required' => 'Vui lòng nhập điểm số.']),
                        TextInput::make('kpi_points_customer_meeting.points')
                            ->label('Điểm / 1 lượt gặp khách')
                            ->numeric()
                            ->step(0.1)
                            ->required()
                            ->validationMessages(['required' => 'Vui lòng nhập điểm số.']),
                        TextInput::make('kpi_points_successful_referral.points')
                            ->label('Điểm / 1 nhân sự giới thiệu thành công')
                            ->numeric()
                            ->step(0.1)
                            ->required()
                            ->validationMessages(['required' => 'Vui lòng nhập điểm số.']),
                        Grid::make(2)
                            ->schema([
                                \Filament\Forms\Components\Placeholder::make('kpi_points_work_day_rate_explanation')
                                    ->hiddenLabel()
                                    ->content(new \Illuminate\Support\HtmlString('
                                        <div class="text-sm text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-900/50 p-3 rounded-lg border border-gray-150 dark:border-gray-800">
                                            <strong>Quy tắc tính điểm chuyên cần:</strong> Điểm được tính theo từng tuần (Thứ 2 – Thứ 6). Nhân viên đi đủ số ngày công quy định trong một tuần sẽ được cộng điểm cho tuần đó.<br/>
                                            <em>Lưu ý:</em> Điểm tính theo tuần, không cộng dồn ngày công qua tuần khác. Nghỉ cuối tuần (Thứ 7, Chủ nhật) không tính vào ngày công.<br/>
                                            <em>Ví dụ:</em> Cấu hình cộng <strong>1 điểm</strong> cho mỗi <strong>5 ngày công</strong>. Nhân viên đi đủ 5 ngày trong tuần → cộng 1 điểm tuần đó. Đi thiếu 1 ngày → không được cộng điểm, ngày công tuần đó không chuyển sang tuần sau.
                                        </div>
                                    '))
                                    ->columnSpanFull(),
                                TextInput::make('kpi_points_work_day_rate.points')
                                    ->label('Số điểm cộng ngày công')
                                    ->numeric()
                                    ->step(0.1)
                                    ->required()
                                    ->validationMessages(['required' => 'Vui lòng nhập điểm số.']),
                                TextInput::make('kpi_points_work_day_rate.days')
                                    ->label('Số ngày công tương ứng')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(1)
                                    ->required()
                                    ->validationMessages([
                                        'required' => 'Vui lòng nhập số ngày công.',
                                        'min' => 'Số ngày công phải lớn hơn 0.',
                                    ]),
                            ]),
                        TextInput::make('kpi_points_absence_penalty.points')
                            ->label('Điểm trừ / 1 lần vắng lịch cố định (Họp, Đào tạo)')
                            ->numeric()
                            ->step(0.1)
                            ->required()
                            ->validationMessages(['required' => 'Vui lòng nhập điểm số.']),
                    ])->columns(2),

                Section::make('Cấu hình chấm công')
                    ->description('Thiết lập công ghi nhận dựa trên thời gian check-in/check-out của nhân viên.')
                    ->schema([
                        Select::make('attendance_no_checkout_work_day.work_day')
                            ->label('Nhân viên không thực hiện check-out trong ngày')
                            ->options([
                                '0' => '0.0 công',
                                '0.5' => '0.5 công',
                                '1' => '1.0 công',
                            ])
                            ->required()
                            ->validationMessages(['required' => 'Vui lòng chọn số công mặc định.']),
                        Select::make('attendance_under_6_hours_work_day.work_day')
                            ->label('Nhân viên làm việc dưới 6 tiếng')
                            ->options([
                                '0' => '0.0 công',
                                '0.5' => '0.5 công',
                                '1' => '1.0 công',
                            ])
                            ->required()
                            ->validationMessages(['required' => 'Vui lòng chọn số công mặc định.']),

                        Grid::make(3)
                            ->schema([
                                GoongLocationInput::make('attendance_office_address.address')
                                    ->label('Địa chỉ trụ sở văn phòng')
                                    ->latitudeField('attendance_office_latitude.latitude')
                                    ->longitudeField('attendance_office_longitude.longitude')
                                    ->placeholder('Nhập địa chỉ văn phòng để tự động định vị...')
                                    ->columnSpan(3),
                                
                                Hidden::make('attendance_office_latitude.latitude'),
                                Hidden::make('attendance_office_longitude.longitude'),

                                TextInput::make('attendance_office_radius_meters.radius')
                                    ->label('Bán kính check-in tối đa (mét)')
                                    ->numeric()
                                    ->default(100)
                                    ->columnSpan(1),
                                TextInput::make('attendance_office_wifi_ssid.wifi_ssid')
                                    ->label('Tên Wifi văn phòng hợp lệ')
                                    ->default('BDS_Office_Wifi')
                                    ->columnSpan(1),
                                TimePicker::make('attendance_shift_start_time.shift_start_time')
                                    ->label('Giờ bắt đầu ca làm việc')
                                    ->native(false)
                                    ->required()
                                    ->validationMessages(['required' => 'Vui lòng chọn giờ bắt đầu ca.'])
                                    ->columnSpan(1),
                            ])
                            ->columnSpanFull(),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach ($state as $key => $value) {
            InventorySetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        \Filament\Notifications\Notification::make()
            ->title('Đã lưu các cấu hình thành công!')
            ->success()
            ->send();
    }
}
