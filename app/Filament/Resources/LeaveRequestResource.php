<?php
namespace App\Filament\Resources;
use App\Filament\Resources\LeaveRequestResource\Pages; use App\Modules\Leave\Interfaces\LeaveServiceInterface; use App\Modules\Leave\Enums\LeaveType; use App\Modules\Leave\Models\Enums\RequestStatus; use App\Modules\Leave\Models\LeaveRequest; use Filament\Forms; use Filament\Notifications\Notification; use Filament\Forms\Form; use Filament\Resources\Resource; use Filament\Tables; use Filament\Tables\Table;
class LeaveRequestResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Nhân sự';
    protected static ?string $modelLabel = 'Đơn nghỉ phép';
    protected static ?string $pluralModelLabel = 'Đơn nghỉ phép';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')
                ->label('Nhân viên')
                ->relationship('user', 'name', function (\Illuminate\Database\Eloquent\Builder $query) {
                    $currentUser = auth()->user();
                    if (!$currentUser) return $query;

                    $query->where('id', '!=', $currentUser->id)
                        ->where('role', '!=', \App\Modules\Auth\Models\Enums\UserRole::BUYER->value)
                        ->where('role', '!=', \App\Modules\Auth\Models\Enums\UserRole::SUPER_ADMIN->value)
                        ->whereNotNull('job_position_id');

                    if ($currentUser->role !== \App\Modules\Auth\Models\Enums\UserRole::SUPER_ADMIN) {
                        $query->where('role', '<=', $currentUser->role->value);
                    }

                    if ($currentUser->role === \App\Modules\Auth\Models\Enums\UserRole::DIRECTOR && $currentUser->branch_id) {
                        $query->where('branch_id', $currentUser->branch_id);
                    }

                    if ($currentUser->role === \App\Modules\Auth\Models\Enums\UserRole::MANAGER && $currentUser->department_id) {
                        $query->where('department_id', $currentUser->department_id);
                    }

                    return $query;
                })
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Select::make('leave_type')
                ->label('Loại nghỉ')
                ->options(self::enumOptions(LeaveType::class))
                ->required(),
            Forms\Components\DatePicker::make('start_date')
                ->label('Từ ngày')
                ->native(false)
                ->displayFormat('d/m/Y')
                ->minDate(fn (string $operation) => $operation === 'create' ? now()->startOfDay() : null)
                ->beforeOrEqual('end_date')
                ->required(),
            Forms\Components\DatePicker::make('end_date')
                ->label('Đến ngày')
                ->native(false)
                ->displayFormat('d/m/Y')
                ->minDate(fn (string $operation) => $operation === 'create' ? now()->startOfDay() : null)
                ->afterOrEqual('start_date')
                ->required()
                ->rule(function (Forms\Get $get, ?\Illuminate\Database\Eloquent\Model $record) {
                    return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                        $startDate = $get('start_date');
                        $endDate = $value;
                        $userId = $get('user_id');

                        if ($startDate && $endDate && $userId) {
                            $query = \App\Modules\Leave\Models\LeaveRequest::query()
                                ->where('user_id', $userId)
                                ->where('status', '!=', \App\Modules\Leave\Models\Enums\RequestStatus::REJECTED->value)
                                ->where('status', '!=', \App\Modules\Leave\Models\Enums\RequestStatus::CANCELLED->value)
                                ->where(function ($q) use ($startDate, $endDate) {
                                    $q->where('start_date', '<=', $endDate)
                                      ->where('end_date', '>=', $startDate);
                                });
                            if ($record) {
                                $query->where('id', '!=', $record->id);
                            }
                            if ($query->exists()) {
                                $fail('Nhân viên đã có yêu cầu nghỉ phép trùng trong khoảng thời gian này.');
                            }
                        }
                    };
                }),
            Forms\Components\Select::make('status')
                ->label('Trạng thái')
                ->options(self::enumOptions(RequestStatus::class))
                ->required(),
            Forms\Components\Textarea::make('reason')
                ->label('Lý do')
                ->required()
                ->columnSpanFull(),
            Forms\Components\Textarea::make('rejection_reason')
                ->label('Lý do từ chối')
                ->columnSpanFull()
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('user.name')
                ->label('Nhân viên')
                ->searchable(),
            Tables\Columns\TextColumn::make('leave_type')
                ->label('Loại nghỉ')
                ->formatStateUsing(fn($state) => $state instanceof LeaveType ? $state->label() : LeaveType::tryFrom((int)$state)?->label()),
            Tables\Columns\TextColumn::make('start_date')
                ->label('Từ')
                ->date('d/m/Y'),
            Tables\Columns\TextColumn::make('end_date')
                ->label('Đến')
                ->date('d/m/Y'),
            Tables\Columns\TextColumn::make('status')
                ->label('Trạng thái')
                ->formatStateUsing(fn($state) => $state instanceof RequestStatus ? $state->label() : RequestStatus::tryFrom((int)$state)?->label())
                ->badge()
                ->color(fn ($state) => match ($state instanceof RequestStatus ? $state->value : (int)$state) {
                    RequestStatus::PENDING->value => 'warning',
                    RequestStatus::APPROVED->value => 'success',
                    RequestStatus::REJECTED->value => 'danger',
                    RequestStatus::CANCELLED->value => 'gray',
                    default => 'primary',
                })
        ])
        ->defaultSort('created_at', 'desc')
        ->actions([
            Tables\Actions\Action::make('approve')
                ->label('Duyệt')
                ->icon('heroicon-o-check')
                ->color('success')
                ->visible(fn(LeaveRequest $record): bool => $record->status === RequestStatus::PENDING)
                ->requiresConfirmation()
                ->action(function (LeaveRequest $record): void {
                    $result = app(LeaveServiceInterface::class)->approveLeaveRequest((string) auth()->id(), (string) $record->id);
                    $result->isError() ? Notification::make()->title($result->getMessage())->danger()->send() : Notification::make()->title($result->getMessage())->success()->send();
                }),
            Tables\Actions\Action::make('reject')
                ->label('Từ chối')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->visible(fn(LeaveRequest $record): bool => $record->status === RequestStatus::PENDING)
                ->form([
                    Forms\Components\Textarea::make('reason')
                        ->label('Lý do từ chối')
                        ->required()
                ])
                ->action(function (LeaveRequest $record, array $data): void {
                    $result = app(LeaveServiceInterface::class)->rejectLeaveRequest((string) auth()->id(), (string) $record->id, (string) $data['reason']);
                    $result->isError() ? Notification::make()->title($result->getMessage())->danger()->send() : Notification::make()->title($result->getMessage())->success()->send();
                }),
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make()
        ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();
        if (!$user) {
            return $query;
        }

        if ($user->role === \App\Modules\Auth\Models\Enums\UserRole::MANAGER) {
            return $query->whereHas('user', function ($q) use ($user) {
                $q->where('department_id', $user->department_id);
            });
        }

        if ($user->role === \App\Modules\Auth\Models\Enums\UserRole::DIRECTOR) {
            return $query->whereHas('user', function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            });
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaveRequests::route('/'),
            'create' => Pages\CreateLeaveRequest::route('/create'),
            'edit' => Pages\EditLeaveRequest::route('/{record}/edit')
        ];
    }

    private static function enumOptions(string $enum): array
    {
        return collect($enum::cases())->mapWithKeys(fn($case) => [$case->value => $case->label()])->all();
    }
}
