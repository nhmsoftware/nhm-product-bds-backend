<?php
namespace App\Filament\Resources;
use Illuminate\Database\Eloquent\Builder;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Filament\Resources\LotDepositRequestResource\Pages; use App\Modules\Area\Interfaces\LotDepositRequestServiceInterface; use App\Modules\Area\Models\Enums\LotDepositRequestStatus; use App\Modules\Area\Models\LotDepositRequest; use Filament\Forms; use Filament\Notifications\Notification; use Filament\Forms\Form; use Filament\Resources\Resource; use Filament\Tables; use Filament\Tables\Table;
class LotDepositRequestResource extends Resource
{
    protected static ?string $model=LotDepositRequest::class; protected static ?string $navigationIcon='heroicon-o-banknotes'; protected static ?string $navigationGroup='Giao dịch'; protected static ?string $modelLabel='Yêu cầu đặt cọc'; protected static ?string $pluralModelLabel='Yêu cầu đặt cọc';
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('area_id')
                ->label('Khu đất')
                ->options(function () {
                    $user = auth()->user();
                    $query = \App\Modules\Area\Models\Area::query();
                    if ($user && $user->role === \App\Modules\Auth\Models\Enums\UserRole::DIRECTOR && $user->branch_id) {
                        $query->where('branch_id', $user->branch_id);
                    }
                    return $query->orderBy('name')->pluck('name', 'id');
                })
                ->searchable()
                ->preload()
                ->required()
                ->live()
                ->dehydrated(false)
                ->afterStateHydrated(function (Forms\Components\Select $component, ?LotDepositRequest $record) {
                    if ($record && $record->lot) {
                        $component->state($record->lot->area_id);
                    }
                }),
            Forms\Components\Select::make('lot_id')
                ->label('Lô đất')
                ->options(fn (Forms\Get $get) => 
                    $get('area_id') 
                        ? \App\Modules\Area\Models\Lot::where('area_id', $get('area_id'))
                            ->with([
                                'lockRequests' => fn ($q) => $q->where('status', \App\Modules\Area\Models\Enums\LotLockRequestStatus::APPROVED->value)->with('user'),
                                'depositRequests' => fn ($q) => $q->whereIn('status', [
                                    \App\Modules\Area\Models\Enums\LotDepositRequestStatus::PENDING->value,
                                    \App\Modules\Area\Models\Enums\LotDepositRequestStatus::APPROVED->value,
                                    \App\Modules\Area\Models\Enums\LotDepositRequestStatus::COMPLETED->value
                                ])->with('user')
                            ])
                            ->get()
                            ->mapWithKeys(function ($lot) {
                                $label = $lot->code;
                                $deposit = $lot->depositRequests->first();
                                $lock = $lot->lockRequests->first();

                                if ($deposit) {
                                    $label .= ' - ' . ($deposit->user?->name ?? 'N/A') . ' - Đã đặt cọc';
                                } elseif ($lock) {
                                    $label .= ' - ' . ($lock->user?->name ?? 'N/A') . ' - Đã lock lô';
                                }

                                return [$lot->id => $label];
                            })
                            ->toArray()
                        : []
                )
                ->searchable()
                ->preload()
                ->required()
                ->disabled(fn (Forms\Get $get) => !$get('area_id'))
                ->rules([
                    fn (Forms\Get $get, ?LotDepositRequest $record): \Closure => function (string $attribute, $value, \Closure $fail) use ($record) {
                        if ($record && $record->lot_id === $value) {
                            return;
                        }
                        $lot = \App\Modules\Area\Models\Lot::find($value);
                        if (!$lot) {
                            return;
                        }
                        if ($lot->is_locked || $lot->status !== \App\Modules\Area\Models\Enums\LotStatus::AVAILABLE) {
                            $fail('Lô đất này đang có người khác khóa, đã giữ chỗ hoặc đã bán. Vui lòng chọn lô đất khác.');
                        }
                    }
                ]),
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
            Forms\Components\Select::make('status')
                ->label('Trạng thái')
                ->options(self::enumOptions(LotDepositRequestStatus::class))
                ->required(),
            Forms\Components\Textarea::make('reject_reason')->label('Lý do từ chối')->columnSpanFull()
        ])->columns(2);
    }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('lot.code')
                    ->label('Lô')
                    ->searchable(),
                Tables\Columns\TextColumn::make('lot.area.name')
                    ->label('Khu'),
                Tables\Columns\TextColumn::make('lot.area.branch.name')
                    ->label('Chi nhánh'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nhân viên')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Trạng thái')
                    ->formatStateUsing(fn($state) => $state instanceof LotDepositRequestStatus ? $state->label() : LotDepositRequestStatus::tryFrom((int)$state)?->label())
                    ->badge()
                    ->color(fn ($state): string => match ($state instanceof LotDepositRequestStatus ? $state : LotDepositRequestStatus::tryFrom((int)$state)) {
                        LotDepositRequestStatus::PENDING => 'warning',
                        LotDepositRequestStatus::APPROVED => 'info',
                        LotDepositRequestStatus::REJECTED => 'danger',
                        LotDepositRequestStatus::COMPLETED => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Duyệt')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn(LotDepositRequest $record): bool => $record->status === LotDepositRequestStatus::PENDING)
                    ->requiresConfirmation()
                    ->action(function (LotDepositRequest $record): void {
                        $result = app(LotDepositRequestServiceInterface::class)->adminApprove((string) $record->id, auth()->user());
                        $result->isError()
                            ? Notification::make()->title($result->getMessage())->danger()->send()
                            : Notification::make()->title($result->getMessage())->success()->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Từ chối')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn(LotDepositRequest $record): bool => $record->status === LotDepositRequestStatus::PENDING)
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Lý do từ chối')
                            ->required()
                    ])
                    ->action(function (LotDepositRequest $record, array $data): void {
                        $result = app(LotDepositRequestServiceInterface::class)->adminReject((string) $record->id, auth()->user(), (string) $data['reason']);
                        $result->isError()
                            ? Notification::make()->title($result->getMessage())->danger()->send()
                            : Notification::make()->title($result->getMessage())->success()->send();
                    }),
                Tables\Actions\Action::make('confirm')
                    ->label('Xác nhận giao dịch')
                    ->icon('heroicon-o-banknotes')
                    ->color('warning')
                    ->visible(fn(LotDepositRequest $record): bool => $record->status === LotDepositRequestStatus::APPROVED)
                    ->requiresConfirmation()
                    ->action(function (LotDepositRequest $record): void {
                        $result = app(LotDepositRequestServiceInterface::class)->adminConfirmTransaction((string) $record->id, auth()->user());
                        $result->isError()
                            ? Notification::make()->title($result->getMessage())->danger()->send()
                            : Notification::make()->title($result->getMessage())->success()->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
            ]);
    }
    public static function getPages(): array { return ['index'=>Pages\ListLotDepositRequests::route('/'),'create'=>Pages\CreateLotDepositRequest::route('/create'),'edit'=>Pages\EditLotDepositRequest::route('/{record}/edit')]; }
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // General Director chỉ được xem yêu cầu đặt cọc của chi nhánh bản thân
        if ($user && $user->role === UserRole::DIRECTOR && $user->branch_id) {
            $query->whereHas('lot.area', function (Builder $q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            });
        }

        return $query;
    }

    private static function enumOptions(string $enum): array { return collect($enum::cases())->mapWithKeys(fn($case)=>[$case->value=>$case->label()])->all(); }
}
