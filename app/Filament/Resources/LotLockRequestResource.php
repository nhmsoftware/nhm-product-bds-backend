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

class LotLockRequestResource extends Resource
{
    protected static ?string $model = LotLockRequest::class;
    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';
    protected static ?string $navigationGroup = 'Giao dịch';
    protected static ?string $modelLabel = 'Yêu cầu lock';
    protected static ?string $pluralModelLabel = 'Yêu cầu lock';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('lot_id')->label('Lô đất')->relationship('lot', 'code')->searchable()->preload()->required(),
            Forms\Components\Select::make('user_id')->label('Nhân viên')->relationship('user', 'name')->searchable()->preload()->required(),
            Forms\Components\Select::make('status')->label('Trạng thái')->options(self::enumOptions(LotLockRequestStatus::class))->required(),
            Forms\Components\DateTimePicker::make('expires_at')->label('Hết hạn duyệt'),
            Forms\Components\DateTimePicker::make('approved_at')->label('Duyệt lúc'),
            Forms\Components\DateTimePicker::make('rejected_at')->label('Từ chối lúc'),
            Forms\Components\Textarea::make('reason')->label('Lý do')->columnSpanFull(),
            Forms\Components\Textarea::make('reject_reason')->label('Lý do từ chối')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('lot.code')->label('Lô')->searchable(),
            Tables\Columns\TextColumn::make('lot.area.name')->label('Khu'),
            Tables\Columns\TextColumn::make('user.name')->label('Nhân viên')->searchable(),
            Tables\Columns\TextColumn::make('status')->label('Trạng thái')->formatStateUsing(fn ($state) => $state instanceof LotLockRequestStatus ? $state->label() : LotLockRequestStatus::tryFrom((int) $state)?->label())->badge(),
            Tables\Columns\TextColumn::make('expires_at')->label('Hết hạn')->dateTime('d/m/Y H:i')->sortable(),
            Tables\Columns\TextColumn::make('reason')->label('Lý do')->limit(40),
            Tables\Columns\TextColumn::make('created_at')->label('Ngày tạo')->dateTime('d/m/Y H:i')->sortable(),
        ])->actions([
            Tables\Actions\Action::make('approve')
                ->label('Duyệt')
                ->icon('heroicon-o-check')
                ->color('success')
                ->visible(fn (LotLockRequest $record): bool => $record->status === LotLockRequestStatus::PENDING)
                ->requiresConfirmation()
                ->action(function (LotLockRequest $record): void {
                    if ($record->expires_at && $record->expires_at->isPast()) {
                        $record->update(['status' => LotLockRequestStatus::EXPIRED->value]);
                        $record->lot?->update(['status' => LotStatus::AVAILABLE->value]);
                        Notification::make()->title('Yêu cầu lock đã hết hạn.')->danger()->send();
                        return;
                    }

                    $record->update([
                        'status' => LotLockRequestStatus::APPROVED->value,
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                    ]);
                    $record->lot?->update(['status' => LotStatus::RESERVED->value]);
                    Notification::make()->title('Duyệt yêu cầu lock thành công.')->success()->send();
                }),
            Tables\Actions\Action::make('reject')
                ->label('Từ chối')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->visible(fn (LotLockRequest $record): bool => $record->status === LotLockRequestStatus::PENDING)
                ->form([Forms\Components\Textarea::make('reason')->label('Lý do từ chối')->required()])
                ->action(function (LotLockRequest $record, array $data): void {
                    $record->update([
                        'status' => LotLockRequestStatus::REJECTED->value,
                        'rejected_by' => auth()->id(),
                        'rejected_at' => now(),
                        'reject_reason' => $data['reason'],
                    ]);
                    $record->lot?->update(['status' => LotStatus::AVAILABLE->value]);
                    Notification::make()->title('Từ chối yêu cầu lock thành công.')->success()->send();
                }),
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
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

    private static function enumOptions(string $enum): array
    {
        return collect($enum::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all();
    }
}
