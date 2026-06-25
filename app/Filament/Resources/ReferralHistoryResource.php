<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReferralHistoryResource\Pages;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Modules\EmployeeReferral\Models\Enums\ReferralStatus;
use App\Modules\EmployeeReferral\Models\Enums\ReferralType;
use App\Modules\EmployeeReferral\Models\ReferralHistory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReferralHistoryResource extends Resource
{
    protected static ?string $model = ReferralHistory::class;

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $navigationGroup = 'Giới thiệu';

    protected static ?string $navigationLabel = 'Lịch sử giới thiệu';

    protected static ?string $modelLabel = 'Lượt giới thiệu';

    protected static ?string $pluralModelLabel = 'Lượt giới thiệu';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('referrer_id')
                ->label('Người giới thiệu')
                ->relationship('referrer', 'name', function (\Illuminate\Database\Eloquent\Builder $query) {
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

            Forms\Components\Select::make('referee_id')
                ->label('Người được giới thiệu')
                ->relationship('referee', 'name', function (\Illuminate\Database\Eloquent\Builder $query) {
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
                ->preload(),

            Forms\Components\TextInput::make('name')
                ->label('Tên')
                ->required(),

            Forms\Components\TextInput::make('phone')
                ->label('SĐT')
                ->tel()
                ->required(),

            Forms\Components\Select::make('referral_type')
                ->label('Loại')
                ->options(self::enumOptions(ReferralType::class))
                ->required(),

            Forms\Components\Select::make('status')
                ->label('Trạng thái')
                ->options(self::enumOptions(ReferralStatus::class))
                ->required(),

            Forms\Components\DateTimePicker::make('scanned_at')
                ->label('Quét lúc'),

            Forms\Components\DateTimePicker::make('registered_at')
                ->label('Đăng ký lúc'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('referrer.name')
                    ->label('Người giới thiệu')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Tên người quét')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('SĐT'),
                Tables\Columns\TextColumn::make('referral_type')
                    ->label('Loại QR')
                    ->formatStateUsing(fn ($state) => $state instanceof ReferralType ? $state->label() : ReferralType::tryFrom((int) $state)?->label())
                    ->badge(),
                Tables\Columns\TextColumn::make('scanned_at')
                    ->label('Thời gian quét')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Trạng thái')
                    ->formatStateUsing(fn ($state) => $state instanceof ReferralStatus ? $state->label() : ReferralStatus::tryFrom((int) $state)?->label())
                    ->badge(),
            ])
            ->defaultSort('scanned_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('referral_type')
                    ->label('Loại QR')
                    ->options(self::enumOptions(ReferralType::class)),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(self::enumOptions(ReferralStatus::class)),
            ])
            ->actions([
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
            'index'  => Pages\ListReferralHistories::route('/'),
            'create' => Pages\CreateReferralHistory::route('/create'),
            'edit'   => Pages\EditReferralHistory::route('/{record}/edit'),
        ];
    }

    private static function enumOptions(string $enum): array
    {
        return collect($enum::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->all();
    }
}
