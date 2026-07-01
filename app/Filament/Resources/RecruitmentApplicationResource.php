<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecruitmentApplicationResource\Pages;
use App\Modules\Auth\Models\Department;
use App\Modules\Auth\Models\EmployeeProfile;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Models\JobPosition;
use App\Modules\Auth\Models\User;
use App\Modules\Branch\Models\Branch;
use App\Modules\Recruitment\Models\RecruitmentApplication;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RecruitmentApplicationResource extends Resource
{
    protected static ?string $model = RecruitmentApplication::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationGroup = 'Nhân sự';
    protected static ?string $modelLabel = 'Duyệt đơn ứng tuyển';
    protected static ?string $pluralModelLabel = 'Duyệt đơn ứng tuyển';

    private const POSITION_OPTIONS = [
        1 => ['role_name' => 'employee', 'label' => 'Nhân viên'],
        2 => ['role_name' => 'tp_kd',    'label' => 'Quản lý'],
        3 => ['role_name' => 'gdkd',     'label' => 'Giám đốc'],
        4 => ['role_name' => 'ceo',      'label' => 'Tổng giám đốc (CEO)'],
    ];

    private const POSITION_FORM_OPTIONS = [
        1 => 'Nhân viên',
        2 => 'Trưởng phòng',
        3 => 'Giám đốc',
    ];

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Thông tin ứng viên')
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->label('Ứng viên')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->disabled(),

                    Forms\Components\Select::make('applied_position')
                        ->label('Vị trí ứng tuyển')
                        ->options(self::POSITION_FORM_OPTIONS)
                        ->required()
                        ->disabled(),

                    Forms\Components\Select::make('applied_branch_id')
                        ->label('Chi nhánh ứng tuyển')
                        ->relationship('appliedBranch', 'name')
                        ->required()
                        ->disabled(),

                    Forms\Components\Select::make('status')
                        ->label('Trạng thái')
                        ->options([
                            'pending'  => 'Chờ duyệt',
                            'approved' => 'Đã duyệt',
                            'rejected' => 'Từ chối',
                        ])
                        ->required()
                        ->disabled(),
                ])->columns(2),

            Forms\Components\Section::make('Hồ sơ ứng tuyển')
                ->schema([
                    Forms\Components\TextInput::make('cv_url')
                        ->label('Link CV / Đính kèm')
                        ->disabled(),

                    Forms\Components\TextInput::make('profile_url')
                        ->label('Link Profile')
                        ->disabled(),

                    Forms\Components\TextInput::make('education')
                        ->label('Học vấn')
                        ->disabled(),

                    Forms\Components\TextInput::make('experience')
                        ->label('Kinh nghiệm')
                        ->disabled(),

                    Forms\Components\Textarea::make('introduction')
                        ->label('Giới thiệu bản thân')
                        ->rows(4)
                        ->disabled()
                        ->columnSpanFull(),
                ])->columns(2),

            Forms\Components\Section::make('Thông tin duyệt đơn')
                ->schema([
                    Forms\Components\Select::make('approved_by')
                        ->label('Người xử lý')
                        ->relationship('approver', 'name')
                        ->disabled(),

                    Forms\Components\DateTimePicker::make('processed_at')
                        ->label('Thời gian xử lý')
                        ->disabled(),

                    Forms\Components\Textarea::make('rejected_reason')
                        ->label('Lý do từ chối')
                        ->rows(3)
                        ->disabled()
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        $positionLabels = array_column(self::POSITION_OPTIONS, 'label');

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Họ và tên')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('appliedBranch.name')
                    ->label('Chi nhánh ứng tuyển')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.departmentRel.name')
                    ->label('Phòng ban')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('applied_position')
                    ->label('Vị trí ứng tuyển')
                    ->formatStateUsing(fn ($state) => $positionLabels[(int) $state] ?? '—'),

                Tables\Columns\TextColumn::make('cv_url')
                    ->label('CV / Tài liệu')
                    ->formatStateUsing(fn ($state) => $state ? 'Xem CV' : 'Không có')
                    ->url(fn ($state) => $state ? asset('storage/' . $state) : null, true)
                    ->color(fn ($state) => $state ? 'primary' : 'gray'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'  => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending'  => 'Chờ duyệt',
                        'approved' => 'Đã duyệt',
                        'rejected' => 'Từ chối',
                        default    => $state,
                    }),

                Tables\Columns\TextColumn::make('processed_at')
                    ->label('Thời gian duyệt')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Phê duyệt')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (RecruitmentApplication $record): bool => $record->status === 'pending')
                    ->fillForm(fn (RecruitmentApplication $record): array => [
                        'role'             => (int) ($record->applied_position ?? 1),
                        'branch_id'        => $record->applied_branch_id,
                        'department_id'    => $record->user?->department_id,
                        'job_position_id'  => self::defaultJobPositionId((int) ($record->applied_position ?? 1)),
                    ])
                    ->form([
                        Forms\Components\Select::make('role')
                            ->label('Vai trò sau duyệt')
                            ->options(array_column(self::POSITION_OPTIONS, 'label'))
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set, $state) => $set(
                                'job_position_id',
                                self::defaultJobPositionId((int) $state),
                            )),

                        Forms\Components\Select::make('branch_id')
                            ->label('Chi nhánh')
                            ->options(function (): array {
                                $query = Branch::query()->where('is_active', true)->orderBy('sort')->orderBy('name');
                                $currentUser = auth()->user();
                                if ($currentUser->role?->name === 'gdkd' && $currentUser->branch_id) {
                                    $query->where('id', $currentUser->branch_id);
                                }
                                return $query->pluck('name', 'id')->all();
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('department_id', null)),

                        Forms\Components\Select::make('department_id')
                            ->label('Phòng ban')
                            ->options(fn (Forms\Get $get): array => Department::query()
                                ->when($get('branch_id'), fn ($query, $branchId) => $query->where('branch_id', $branchId))
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('job_position_id')
                            ->label('Chức danh')
                            ->options(fn (): array => JobPosition::query()->orderBy('id')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->action(function (RecruitmentApplication $record, array $data): void {
                        $user = $record->user;
                        if (!$user) {
                            Notification::make()
                                ->title('Không tìm thấy tài khoản ứng viên')
                                ->danger()
                                ->send();
                            return;
                        }

                        $department = Department::find($data['department_id']);
                        if (!$department || $department->branch_id !== $data['branch_id']) {
                            Notification::make()
                                ->title('Phòng ban không thuộc chi nhánh đã chọn')
                                ->danger()
                                ->send();
                            return;
                        }

                        $positionInt = (int) $data['role'];
                        $roleName = self::POSITION_OPTIONS[$positionInt]['role_name'] ?? 'employee';
                        $roleId = Role::where('name', $roleName)->value('id');

                        $user->forceFill([
                            'role_id'          => $roleId,
                            'branch_id'        => $data['branch_id'],
                            'department_id'    => $data['department_id'],
                            'job_position_id'  => (int) $data['job_position_id'],
                            'is_active'        => true,
                        ])->save();

                        EmployeeProfile::updateOrCreate(
                            ['user_id' => $user->id],
                            [
                                'employee_title' => JobPosition::find($data['job_position_id'])?->name,
                                'education'      => $record->education,
                                'experience'     => $record->experience,
                                'attachments'    => $record->cv_url ? [[
                                    'type' => 'CV ứng tuyển',
                                    'name' => 'CV ứng tuyển',
                                    'url'  => $record->cv_url,
                                ]] : null,
                            ]
                        );

                        $record->update([
                            'applied_position'  => $positionInt,
                            'applied_branch_id' => $data['branch_id'],
                            'status'            => 'approved',
                            'approved_by'       => auth()->id(),
                            'processed_at'      => now(),
                        ]);

                        Notification::make()
                            ->title('Duyệt đơn ứng tuyển thành công')
                            ->body('Đã cập nhật vai trò, chi nhánh, phòng ban và tạo hồ sơ nhân sự cho ứng viên.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Từ chối')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (RecruitmentApplication $record): bool => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('rejected_reason')
                            ->label('Lý do từ chối')
                            ->required()
                            ->validationMessages([
                                'required' => 'Vui lòng nhập lý do từ chối.',
                            ]),
                    ])
                    ->action(function (RecruitmentApplication $record, array $data): void {
                        $record->update([
                            'status'           => 'rejected',
                            'rejected_reason'  => $data['rejected_reason'],
                            'approved_by'      => auth()->id(),
                            'processed_at'     => now(),
                        ]);

                        Notification::make()
                            ->title('Đã từ chối đơn ứng tuyển')
                            ->danger()
                            ->send();
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function defaultJobPositionId(int $position): int
    {
        return match ($position) {
            2       => JobPosition::BUSINESS_MANAGER,
            3       => JobPosition::BUSINESS_DIRECTOR,
            4       => JobPosition::CEO,
            default => JobPosition::BUSINESS_SPECIALIST,
        };
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();
        if (!$user) {
            return $query;
        }

        if ($user->hasAnyPermission(['manage_all', 'manage_employees'])) {
            return $query;
        }

        if ($user->role?->name === 'gdkd' && $user->branch_id) {
            return $query->where('applied_branch_id', $user->branch_id);
        }

        return $query->whereRaw('1 = 0');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecruitmentApplications::route('/'),
            'edit'  => Pages\EditRecruitmentApplication::route('/{record}/edit'),
        ];
    }
}
