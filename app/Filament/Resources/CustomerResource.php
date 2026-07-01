<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class CustomerResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'CRM/Tư vấn';

    protected static ?string $modelLabel = 'Khách hàng';

    protected static ?string $pluralModelLabel = 'Khách hàng';

    protected static ?string $navigationLabel = 'Quản lý khách hàng';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Thông tin khách hàng')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Họ tên')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('Số điện thoại')
                            ->tel()
                            ->required()
                            ->maxLength(20),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->validationMessages(['unique' => 'Email đã tồn tại.']),
                        Forms\Components\TextInput::make('cccd')
                            ->label('Số CCCD')
                            ->maxLength(20),
                        Forms\Components\TextInput::make('address')
                            ->label('Địa chỉ')
                            ->maxLength(255),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Đang hoạt động')
                            ->default(true),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Bảo mật')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->label('Mật khẩu')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state)),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Họ tên')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Số điện thoại')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('cccd')
                    ->label('CCCD')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('address')
                    ->label('Địa chỉ')
                    ->limit(32)
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Hoạt động')
                    ->boolean(),
                Tables\Columns\TextColumn::make('lock_status')
                    ->label('Trạng thái')
                    ->getStateUsing(fn (User $record): string => $record->isLocked() ? 'Đang khóa' : 'Bình thường')
                    ->badge()
                    ->alignCenter()
                    ->color(fn (User $record): string => $record->isLocked() ? 'danger' : 'success')
                    ->description(fn (User $record): ?string => $record->isLocked() && $record->lock_expires_at
                        ? $record->lock_expires_at->format('d/m/Y H:i')
                        : null),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
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
            ])
            ->actions([
                Tables\Actions\Action::make('lock')
                    ->label('Khóa')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->visible(fn (User $record): bool => ! $record->isLocked())
                    ->form([
                        Forms\Components\Textarea::make('lock_reason')
                            ->label('Lý do khóa')
                            ->required()
                            ->maxLength(500),
                        Forms\Components\TextInput::make('lock_days')
                            ->label('Số ngày khóa')
                            ->numeric()
                            ->default(2)
                            ->minValue(1)
                            ->required()
                            ->suffix('ngày'),
                    ])
                    ->action(function (User $record, array $data): void {
                        $record->lock(
                            reason: $data['lock_reason'],
                            days: max(1, (int) $data['lock_days']),
                            lockedBy: auth()->user()
                        );

                        Notification::make()->title('Khóa khách hàng thành công.')->success()->send();
                    }),
                Tables\Actions\Action::make('unlock')
                    ->label('Mở khóa')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->visible(fn (User $record): bool => $record->isLocked())
                    ->requiresConfirmation()
                    ->modalHeading('Mở khóa khách hàng')
                    ->modalSubheading(fn (User $record): string => "Bạn có chắc chắn muốn mở khóa khách hàng \"{$record->name}\"?")
                    ->action(function (User $record): void {
                        $record->unlock();
                        Notification::make()->title('Mở khóa khách hàng thành công.')->success()->send();
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('role', fn (Builder $query) => $query->where('name', 'buyer'));
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['role_id'] = Role::query()->where('name', 'buyer')->value('id');
        $data['staff_code'] = null;
        $data['department_id'] = null;
        $data['job_position_id'] = null;
        $data['branch_id'] = null;

        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
