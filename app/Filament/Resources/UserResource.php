<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Modules\Area\Models\Area;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Modules\Auth\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Nhân sự';
    protected static ?string $modelLabel = 'Tài khoản';
    protected static ?string $pluralModelLabel = 'Tài khoản';
    protected static ?string $navigationLabel = 'Danh sách tài khoản';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Thông tin tài khoản')->schema([
                Forms\Components\TextInput::make('staff_code')
                    ->label('Mã nhân viên')
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->validationMessages(['unique' => 'Mã nhân viên đã tồn tại.']),
                Forms\Components\TextInput::make('name')->label('Họ tên')->required()->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->validationMessages(['unique' => 'Email đã tồn tại.']),
                Forms\Components\TextInput::make('phone')->label('Số điện thoại')->tel()->required()->maxLength(20),
                Forms\Components\TextInput::make('password')
                    ->label('Mật khẩu')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state)),
                Forms\Components\Select::make('role')
                    ->label('Vai trò')
                    ->options(function () {
                        $currentUser = auth()->user();
                        $options = self::enumOptions(UserRole::class);
                        if (!$currentUser) {
                            return $options;
                        }
                        $currentRoleVal = $currentUser->role instanceof UserRole ? $currentUser->role->value : (int) $currentUser->role;
                        
                        return collect(UserRole::cases())
                            ->filter(function ($case) use ($currentRoleVal) {
                                if ($case === UserRole::BUYER) {
                                    return true;
                                }
                                return $case->value <= $currentRoleVal;
                            })
                            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
                            ->all();
                    })
                    ->required()
                    ->live(),
                Forms\Components\Toggle::make('is_active')->label('Đang hoạt động')->default(true),
            ])->columns(2),
            Forms\Components\Section::make('Phân quyền và phòng ban')->schema([
                Forms\Components\Select::make('branch_id')
                    ->label('Chi nhánh')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn (Forms\Set $set) => $set('department_id', null))
                    ->required(fn (Forms\Get $get): bool => in_array((int) $get('role'), [UserRole::EMPLOYEE->value, UserRole::MANAGER->value, UserRole::DIRECTOR->value])),
                Forms\Components\Select::make('department_id')
                    ->label('Phòng ban')
                    ->relationship(
                        name: 'departmentRel',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (\Illuminate\Database\Eloquent\Builder $query, Forms\Get $get) => $query
                            ->when($get('branch_id'), fn ($q, $branchId) => $q->where('branch_id', $branchId))
                            ->when(!$get('branch_id'), fn ($q) => $q->whereNull('id'))
                    )
                    ->searchable()
                    ->preload()
                    ->disabled(fn (Forms\Get $get): bool => !$get('branch_id'))
                    ->required(fn (Forms\Get $get): bool => in_array((int) $get('role'), [UserRole::EMPLOYEE->value, UserRole::MANAGER->value, UserRole::DIRECTOR->value])),
                Forms\Components\Select::make('job_position_id')
                    ->label('Chức danh')
                    ->relationship('jobPosition', 'name')
                    ->searchable()
                    ->preload()
                    ->required(fn (Forms\Get $get): bool => in_array((int) $get('role'), [UserRole::EMPLOYEE->value, UserRole::MANAGER->value, UserRole::DIRECTOR->value])),
                Forms\Components\Select::make('assigned_area_ids')
                    ->label('Khu đất được cấp quyền')
                    ->multiple()
                    ->options(fn () => Area::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->preload()
                    ->searchable()
                    ->dehydrated(false)
                    ->afterStateHydrated(fn (Forms\Components\Select $component, ?User $record) => $component->state($record?->assignedAreas()->pluck('areas.id')->all() ?? []))
                    ->saveRelationshipsUsing(function (User $record, ?array $state): void {
                        $existing = $record->assignedAreas()->pluck('areas.id')->all();
                        $next = $state ?? [];
                        $record->assignedAreas()->detach(array_values(array_diff($existing, $next)));
                        foreach (array_diff($next, $existing) as $areaId) {
                            $record->assignedAreas()->attach($areaId, ['id' => (string) Str::uuid()]);
                        }
                    }),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('staff_code')->label('Mã NV')->searchable(),
            Tables\Columns\TextColumn::make('name')->label('Họ tên')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('email')->label('Email')->searchable(),
            Tables\Columns\TextColumn::make('role')
                ->label('Vai trò')
                ->formatStateUsing(fn ($state) => $state instanceof UserRole ? $state->label() : UserRole::tryFrom((int) $state)?->label()),
            Tables\Columns\IconColumn::make('is_active')->label('Hoạt động')->boolean(),
            Tables\Columns\TextColumn::make('departmentRel.name')->label('Phòng ban')->toggleable()->placeholder('-'),
            Tables\Columns\TextColumn::make('branch.name')->label('Chi nhánh')->toggleable()->placeholder('-'),
            Tables\Columns\TextColumn::make('jobPosition.name')->label('Chức danh')->toggleable()->placeholder('-'),
            Tables\Columns\TextColumn::make('created_at')->label('Ngày tạo')->dateTime('d/m/Y H:i')->sortable(),
            Tables\Columns\TextColumn::make('lock_status')
                ->label('Trạng thái')
                ->getStateUsing(fn (User $record): string => $record->isLocked() ? 'Đang khóa' : 'Bình thường')
                ->badge()
                ->alignCenter()
                ->color(fn (User $record): string => $record->isLocked() ? 'danger' : 'success')
                ->description(fn (User $record): ?string => $record->isLocked() && $record->lock_expires_at
                    ? $record->lock_expires_at->format('d/m/Y H:i')
                    : null),
        ])->filters([
            Tables\Filters\SelectFilter::make('role')->label('Vai trò')->options(self::enumOptions(UserRole::class)),
            Tables\Filters\TernaryFilter::make('is_active')->label('Hoạt động'),
            Tables\Filters\SelectFilter::make('locked_at')
                ->label('Trạng thái khóa')
                ->options([
                    'locked' => 'Đang khóa',
                    'unlocked' => 'Chưa khóa',
                ])
                ->query(function (Builder $query, array $state): Builder {
                    if (($state['value'] ?? '') === 'locked') {
                        return $query->whereNotNull('locked_at');
                    }
                    if (($state['value'] ?? '') === 'unlocked') {
                        return $query->whereNull('locked_at');
                    }
                    return $query;
                }),
        ])->actions([
            Tables\Actions\Action::make('lock')
                ->label('Khóa')
                ->icon('heroicon-o-lock-closed')
                ->color('danger')
                ->visible(fn (User $record): bool => $record->id !== auth()->id() && ! $record->isLocked())
                ->form([
                    Forms\Components\Textarea::make('lock_reason')
                        ->label('Lý do khóa')
                        ->required()
                        ->maxLength(500)
                        ->placeholder('Nhập lý do khóa tài khoản...'),
                    Forms\Components\TextInput::make('lock_days')
                        ->label('Số ngày khóa')
                        ->numeric()
                        ->default(2)
                        ->minValue(1)
                        ->required()
                        ->suffix('ngày'),
                ])
                ->action(function (User $record, array $data): void {
                    if ($record->id === auth()->id()) {
                        Notification::make()->title('Không thể khóa tài khoản đang đăng nhập.')->danger()->send();
                        return;
                    }

                    if ($record->isLocked()) {
                        Notification::make()->title('Tài khoản đang bị khóa. Vui lòng mở khóa trước.')->warning()->send();
                        return;
                    }

                    $days = $data['lock_days'] > 0 ? (int) $data['lock_days'] : 2;

                    $record->lock(
                        reason: $data['lock_reason'],
                        days: $days,
                        lockedBy: auth()->user()
                    );

                    Notification::make()->title('Khóa tài khoản thành công.')->success()->send();
                }),
            Tables\Actions\Action::make('unlock')
                ->label('Mở khóa')
                ->icon('heroicon-o-lock-open')
                ->color('success')
                ->visible(fn (User $record): bool => $record->isLocked())
                ->requiresConfirmation()
                ->modalHeading('Mở khóa tài khoản')
                ->modalSubheading(fn (User $record): string => "Bạn có chắc chắn muốn mở khóa tài khoản \"{$record->name}\"?")
                ->action(function (User $record): void {
                    if (! $record->isLocked()) {
                        Notification::make()->title('Tài khoản đã được mở khóa trước đó.')->warning()->send();
                        return;
                    }

                    $record->unlock();

                    Notification::make()->title('Mở khóa tài khoản thành công.')->success()->send();
                }),
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])->bulkActions([
            Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $currentUser = auth()->user();

        if ($currentUser) {
            // 1. Ẩn tài khoản của chính mình
            $query->where('id', '!=', $currentUser->id);

            // 2. Ẩn các tài khoản có vai trò cao hơn vai trò của bản thân
            $currentRoleVal = $currentUser->role instanceof UserRole ? $currentUser->role->value : (int) $currentUser->role;

            if ($currentRoleVal !== UserRole::SUPER_ADMIN->value) {
                $query->where(function ($q) use ($currentRoleVal) {
                    $q->where('role', '<=', $currentRoleVal)
                      ->orWhere('role', UserRole::BUYER->value);
                });
            }
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    private static function enumOptions(string $enum): array
    {
        return collect($enum::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all();
    }
}
