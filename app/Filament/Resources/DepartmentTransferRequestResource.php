<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DepartmentTransferRequestResource\Pages;
use App\Modules\Auth\Models\Department;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Modules\Auth\Models\User;
use App\Modules\DepartmentTransfer\Interfaces\DepartmentTransferServiceInterface;
use App\Modules\DepartmentTransfer\Models\DepartmentTransferRequest;
use App\Modules\DepartmentTransfer\Models\Enums\RequestStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DepartmentTransferRequestResource extends Resource
{
    protected static ?string $model = DepartmentTransferRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?string $navigationGroup = 'Nhân sự';

    protected static ?string $modelLabel = 'Yêu cầu chuyển phòng';

    protected static ?string $pluralModelLabel = 'Yêu cầu chuyển phòng';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Nhân viên')
                    ->relationship('user', 'name', function (\Illuminate\Database\Eloquent\Builder $query) {
                        $currentUser = auth()->user();
                        if (!$currentUser) return $query;
                        return $query
                            ->where('id', '!=', $currentUser->id)
                            ->where('role', '!=', UserRole::BUYER->value)
                            ->where('role', '!=', UserRole::SUPER_ADMIN->value)
                            ->where('role', '<=', $currentUser->role->value)
                            ->whereNotNull('job_position_id');
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, ?string $state) {
                        $dept = 'Chưa có';
                        if ($state) {
                            $user = User::find($state);
                            $dept = $user?->department ?? 'Chưa có';
                        }
                        $set('current_department', $dept);
                    }),

                Forms\Components\TextInput::make('current_department')
                    ->label('Phòng ban hiện tại')
                    ->default('Chưa có')
                    ->disabled()
                    ->dehydrated(true),

                Forms\Components\Select::make('target_department')
                    ->label('Phòng ban muốn chuyển')
                    ->options(fn () => Department::where('is_active', true)->pluck('name', 'name'))
                    ->searchable()
                    ->required(),

                Forms\Components\DatePicker::make('desired_transfer_date')
                    ->label('Ngày mong muốn chuyển')
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->minDate(today()),

                Forms\Components\Select::make('status')
                    ->label('Trạng thái')
                    ->options(self::enumOptions(RequestStatus::class))
                    ->required(),

                Forms\Components\Textarea::make('reason')
                    ->label('Lý do')
                    ->required()
                    ->validationMessages([
                        'required' => 'Vui lòng nhập lý do.',
                    ])
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('rejection_reason')
                    ->label('Lý do từ chối')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nhân viên')
                    ->searchable(),

                Tables\Columns\TextColumn::make('current_department')
                    ->label('Từ phòng'),

                Tables\Columns\TextColumn::make('target_department')
                    ->label('Sang phòng'),

                Tables\Columns\TextColumn::make('desired_transfer_date')
                    ->label('Ngày mong muốn')
                    ->date('d/m/Y'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Trạng thái')
                    ->formatStateUsing(fn ($state) => $state instanceof RequestStatus
                        ? $state->label()
                        : RequestStatus::tryFrom((int) $state)?->label()
                    )
                    ->badge()
                    ->color(fn ($state) => match ($state instanceof RequestStatus ? $state->value : (int) $state) {
                        RequestStatus::PENDING->value  => 'warning',
                        RequestStatus::APPROVED->value => 'success',
                        RequestStatus::REJECTED->value => 'danger',
                        RequestStatus::CANCELLED->value => 'gray',
                        default                        => 'primary',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(self::enumOptions(RequestStatus::class)),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Duyệt')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (DepartmentTransferRequest $record): bool => $record->status === RequestStatus::PENDING)
                    ->requiresConfirmation()
                    ->modalHeading('Xác nhận duyệt')
                    ->modalDescription('Bạn có chắc muốn duyệt yêu cầu chuyển phòng này không?')
                    ->action(function (DepartmentTransferRequest $record): void {
                        $result = app(DepartmentTransferServiceInterface::class)
                            ->approveDepartmentTransferRequest((string) auth()->id(), (string) $record->id);

                        $result->isError()
                            ? Notification::make()->title($result->getMessage())->danger()->send()
                            : Notification::make()->title($result->getMessage())->success()->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Từ chối')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (DepartmentTransferRequest $record): bool => $record->status === RequestStatus::PENDING)
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Lý do từ chối')
                            ->required()
                            ->validationMessages([
                                'required' => 'Vui lòng nhập lý do từ chối.',
                            ]),
                    ])
                    ->action(function (DepartmentTransferRequest $record, array $data): void {
                        $result = app(DepartmentTransferServiceInterface::class)
                            ->rejectDepartmentTransferRequest((string) auth()->id(), (string) $record->id, (string) $data['reason']);

                        $result->isError()
                            ? Notification::make()->title($result->getMessage())->danger()->send()
                            : Notification::make()->title($result->getMessage())->success()->send();
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDepartmentTransferRequests::route('/'),
            'create' => Pages\CreateDepartmentTransferRequest::route('/create'),
            'edit'   => Pages\EditDepartmentTransferRequest::route('/{record}/edit'),
        ];
    }

    private static function enumOptions(string $enum): array
    {
        return collect($enum::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->all();
    }
}
