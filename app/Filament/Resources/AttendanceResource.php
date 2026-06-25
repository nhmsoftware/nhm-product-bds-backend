<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Modules\Attendance\Models\Attendance;
use App\Modules\Attendance\Models\Enums\AttendanceStatus;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Filament\Support\GoongLocationInput;
use Filament\Forms\Components\Hidden;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Nhân sự';
    protected static ?string $modelLabel = 'Chấm công';
    protected static ?string $pluralModelLabel = 'Chấm công';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')
                ->label('Nhân viên')
                ->relationship('user', 'name', function (Builder $query) {
                    $currentUser = auth()->user();
                    if (!$currentUser) return $query;
                    $query->where('id', '!=', $currentUser->id)
                          ->where('role', '!=', UserRole::BUYER->value)
                          ->where('role', '!=', UserRole::SUPER_ADMIN->value)
                          ->whereNotNull('job_position_id');
                    if ($currentUser->role !== UserRole::SUPER_ADMIN) {
                        $query->where('role', '<=', $currentUser->role->value);
                    }
                    if ($currentUser->role === UserRole::DIRECTOR && $currentUser->branch_id) {
                        $query->where('branch_id', $currentUser->branch_id);
                    }
                    if ($currentUser->role === UserRole::MANAGER && $currentUser->department_id) {
                        $query->where('department_id', $currentUser->department_id);
                    }
                    return $query;
                })
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\DatePicker::make('work_date')
                ->label('Ngày làm')
                ->native(false)
                ->displayFormat('d/m/Y')
                ->required(),
            Forms\Components\DateTimePicker::make('check_in_at')
                ->label('Check-in')
                ->native(false)
                ->timezone('Asia/Ho_Chi_Minh')
                ->displayFormat('d/m/Y H:i')
                ->beforeOrEqual('check_out_at'),
            Forms\Components\DateTimePicker::make('check_out_at')
                ->label('Check-out')
                ->native(false)
                ->timezone('Asia/Ho_Chi_Minh')
                ->displayFormat('d/m/Y H:i')
                ->afterOrEqual('check_in_at'),
            Forms\Components\Select::make('status')
                ->label('Trạng thái')
                ->options(self::enumOptions(AttendanceStatus::class))
                ->required(),
            Forms\Components\Select::make('check_in_method')
                ->label('Cách check-in')
                ->options(['gps' => 'GPS', 'wifi' => 'WiFi'])
                ->placeholder('Chọn cách check-in')
                ->live(),
            Forms\Components\Select::make('check_out_method')
                ->label('Cách check-out')
                ->options(['gps' => 'GPS', 'wifi' => 'WiFi'])
                ->placeholder('Chọn cách check-out')
                ->live(),

            GoongLocationInput::make('check_in_location')
                ->label('Vị trí Check-in')
                ->latitudeField('check_in_lat')
                ->longitudeField('check_in_lng')
                ->placeholder('Nhập địa chỉ để định vị check-in tự động...')
                ->dehydrated(false)
                ->visible(fn (Forms\Get $get) => $get('check_in_method') === 'gps')
                ->columnSpanFull(),

            GoongLocationInput::make('check_out_location')
                ->label('Vị trí Check-out')
                ->latitudeField('check_out_lat')
                ->longitudeField('check_out_lng')
                ->placeholder('Nhập địa chỉ để định vị check-out tự động...')
                ->dehydrated(false)
                ->visible(fn (Forms\Get $get) => $get('check_out_method') === 'gps')
                ->columnSpanFull(),

            Hidden::make('check_in_lat'),
            Hidden::make('check_in_lng'),
            Hidden::make('check_out_lat'),
            Hidden::make('check_out_lng'),
            Forms\Components\Textarea::make('note')->label('Ghi chú')->columnSpanFull()
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('user.name')->label('Nhân viên')->searchable(),
            Tables\Columns\TextColumn::make('work_date')->label('Ngày')->date('d/m/Y'),
            Tables\Columns\TextColumn::make('check_in_at')->label('Vào')->dateTime('H:i', 'Asia/Ho_Chi_Minh'),
            Tables\Columns\TextColumn::make('check_out_at')->label('Ra')->dateTime('H:i', 'Asia/Ho_Chi_Minh'),
            Tables\Columns\TextColumn::make('computed_work_day')
                ->label('Công')
                ->getStateUsing(function ($record) {
                    $checkOutAt = $record->check_out_at;
                    if (!$checkOutAt) {
                        return '-';
                    }
                    $checkInAt = $record->check_in_at;
                    if (!$checkInAt) {
                        return '-';
                    }
                    $durationSeconds = max(0, (int) abs(\Carbon\Carbon::parse($checkOutAt)->diffInSeconds(\Carbon\Carbon::parse($checkInAt))));
                    if ($durationSeconds >= 21600) {
                        return '1.0';
                    }
                    $status = $record->status;
                    if ($status === AttendanceStatus::PRESENT || $status === AttendanceStatus::LATE) {
                        return '1.0';
                    }
                    if ($status === AttendanceStatus::HALF_DAY) {
                        return '0.5';
                    }
                    return '0.0';
                })
                ->sortable(),
            Tables\Columns\TextColumn::make('status')
                ->label('Trạng thái')
                ->formatStateUsing(fn ($state) => $state instanceof AttendanceStatus ? $state->label() : AttendanceStatus::tryFrom((int)$state)?->label())
                ->badge()
                ->color(fn ($state): string => match (
                    $state instanceof AttendanceStatus ? $state : AttendanceStatus::tryFrom((int)$state)
                ) {
                    AttendanceStatus::PRESENT   => 'success',   // xanh lá — Có mặt
                    AttendanceStatus::LATE      => 'warning',   // cam     — Đi muộn
                    AttendanceStatus::ABSENT    => 'danger',    // đỏ      — Vắng mặt
                    AttendanceStatus::HALF_DAY  => 'info',      // xanh dương — Nửa ngày
                    AttendanceStatus::WORKING   => 'primary',   // tím     — Đang làm việc
                    default                     => 'gray',
                })
        ])->defaultSort('created_at', 'desc')->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make()
        ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Giám đốc chi nhánh (DIRECTOR) chỉ được xem dữ liệu chấm công
     * của nhân viên thuộc chi nhánh của mình.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && $user->role === UserRole::DIRECTOR && $user->branch_id) {
            $query->whereHas('user', function (Builder $q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            });
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit')
        ];
    }

    private static function enumOptions(string $enum): array
    {
        return collect($enum::cases())->mapWithKeys(fn($case) => [$case->value => $case->label()])->all();
    }
}
