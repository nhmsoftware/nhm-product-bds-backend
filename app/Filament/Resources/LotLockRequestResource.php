<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LotLockRequestResource\Pages;
use App\Modules\Area\Models\Enums\LotLockRequestStatus;
use App\Modules\Area\Models\Enums\LotStatus;
use App\Modules\Area\Models\LotLockRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Modules\Auth\Models\Enums\UserRole;

class LotLockRequestResource extends Resource
{
    protected static ?string $model = LotLockRequest::class;
    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';
    protected static ?string $navigationGroup = 'Giao dịch';
    protected static ?string $modelLabel = 'Yêu cầu lock lô';
    protected static ?string $pluralModelLabel = 'Yêu cầu lock lô';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('area_id')
                ->label('Khu đất')
                ->options(function () {
                    $user = auth()->user();
                    $query = \App\Modules\Area\Models\Area::query();
                    if ($user && $user->role?->name === 'gdkd' && $user->branch_id) {
                        $query->where('branch_id', $user->branch_id);
                    }
                    return $query->orderBy('name')->pluck('name', 'id');
                })
                ->searchable()
                ->preload()
                ->required()
                ->live()
                ->dehydrated(false)
                ->afterStateHydrated(function (Forms\Components\Select $component, ?LotLockRequest $record) {
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
                    fn (Forms\Get $get, ?LotLockRequest $record): \Closure => function (string $attribute, $value, \Closure $fail) use ($record) {
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
                          ->where('role_id', '!=', \App\Modules\Auth\Models\Role::where('name', 'buyer')->value('id'))
                          ->where('role_id', '!=', \App\Modules\Auth\Models\Role::where('name', 'super_admin')->value('id'))
                          ->whereNotNull('job_position_id');

                    if (!$currentUser->hasAnyPermission(['manage_all', 'approve_onboard'])) {
                        $query->whereHas('role', fn($q) => $q->where('level', '>=', $currentUser->role?->level ?? 999));
                    }

                    if ($currentUser->role?->name === 'gdkd' && $currentUser->branch_id) {
                        $query->where('branch_id', $currentUser->branch_id);
                    }

                    if ($currentUser->role?->name === 'tp_kd' && $currentUser->department_id) {
                        $query->where('department_id', $currentUser->department_id);
                    }

                    return $query;
                })
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\TextInput::make('customer_name')
                ->label('Tên khách hàng')
                ->maxLength(255),
            Forms\Components\Select::make('status')->label('Trạng thái')->options(self::enumOptions(LotLockRequestStatus::class))->required(),
            Forms\Components\DateTimePicker::make('expires_at')
                ->label('Hết hạn lock')
                ->native(false)
                ->displayFormat('d/m/Y H:i')
                ->minDate(now())
                ->validationMessages([
                    'after_or_equal' => 'Thời gian hết hạn không được ở trong quá khứ.',
                ]),
            Forms\Components\DateTimePicker::make('approved_at')->label('Duyệt lúc')->disabled(),
            Forms\Components\DateTimePicker::make('rejected_at')->label('Từ chối lúc')->disabled(),
            Forms\Components\Textarea::make('reason')
                ->label('Lý do lock lô')
                ->columnSpanFull()
                ->maxLength(1000),
            Forms\Components\Textarea::make('reject_reason')->label('Lý do từ chối')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('lot.code')
                ->label('Mã lô')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('lot.area.name')
                ->label('Tên khu đất')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('user.name')
                ->label('Nhân viên yêu cầu')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('customer_name')
                ->label('Tên khách hàng')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('reason')
                ->label('Lý do lock lô')
                ->limit(30)
                ->searchable(),
            Tables\Columns\TextColumn::make('status')
                ->label('Trạng thái')
                ->formatStateUsing(fn ($state) => $state instanceof LotLockRequestStatus ? $state->label() : LotLockRequestStatus::tryFrom((int) $state)?->label())
                ->badge()
                ->color(fn ($state): string => match ($state instanceof LotLockRequestStatus ? $state : LotLockRequestStatus::tryFrom((int) $state)) {
                    LotLockRequestStatus::APPROVED => 'success',
                    LotLockRequestStatus::REJECTED => 'danger',
                    LotLockRequestStatus::EXPIRED  => 'gray',
                    default => 'gray',
                })
                ->sortable(),
            Tables\Columns\TextColumn::make('created_at')
                ->label('Thời gian yêu cầu')
                ->dateTime('d/m/Y H:i')
                ->sortable(),
            Tables\Columns\TextColumn::make('expires_at')
                ->label('Thời gian hết hạn')
                ->dateTime('d/m/Y H:i')
                ->sortable(),
        ])
        ->defaultSort('created_at', 'desc')
        ->filters([
            Tables\Filters\SelectFilter::make('area')
                ->relationship('lot.area', 'name')
                ->label('Khu đất')
                ->preload(),
            Tables\Filters\SelectFilter::make('status')
                ->label('Trạng thái')
                ->options(self::enumOptions(LotLockRequestStatus::class)),
        ])->actions([
            Tables\Actions\Action::make('reject')
                ->label('Từ chối')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->visible(fn (LotLockRequest $record): bool => $record->status === LotLockRequestStatus::APPROVED)
                ->form([Forms\Components\Textarea::make('reason')->label('Lý do từ chối')->required()])
                ->action(function (LotLockRequest $record, array $data): void {
                    $record->update([
                        'status' => LotLockRequestStatus::REJECTED->value,
                        'rejected_by' => auth()->id(),
                        'rejected_at' => now(),
                        'reject_reason' => $data['reason'],
                    ]);
                    $record->lot?->update(['status' => LotStatus::AVAILABLE->value, 'is_locked' => false]);
                    Notification::make()->title('Từ chối lock lô thành công.')->success()->send();
                }),
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLotLockRequests::route('/'),
            'create' => Pages\CreateLotLockRequest::route('/create'),
            'edit' => Pages\EditLotLockRequest::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // General Director chỉ được xem yêu cầu lock của chi nhánh bản thân
        if ($user && $user->role?->name === 'gdkd' && $user->branch_id) {
            $query->whereHas('lot.area', function (Builder $q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            });
        }

        return $query;
    }

    private static function enumOptions(string $enum): array
    {
        return collect($enum::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all();
    }
}
