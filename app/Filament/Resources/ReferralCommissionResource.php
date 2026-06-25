<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReferralCommissionResource\Pages;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Modules\EmployeeReferral\Models\Enums\CommissionPaymentStatus;
use App\Modules\EmployeeReferral\Models\ReferralCommission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReferralCommissionResource extends Resource
{
    protected static ?string $model = ReferralCommission::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Giới thiệu';

    protected static ?string $modelLabel = 'Hoa hồng giới thiệu';

    protected static ?string $pluralModelLabel = 'Hoa hồng giới thiệu';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('referrer_id')
                ->label('Nhân viên')
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

            Forms\Components\Select::make('referral_history_id')
                ->label('Lượt giới thiệu')
                ->relationship('referralHistory', 'name')
                ->searchable()
                ->preload()
                ->required(),

            Forms\Components\TextInput::make('amount')
                ->label('Số điểm')
                ->numeric()
                ->disabled()
                ->dehydrated()
                ->default(function () {
                    $setting = \App\Modules\Area\Models\InventorySetting::where('key', 'kpi_points_successful_referral')->first()?->value;
                    return data_get($setting, 'points', 1.0);
                })
                ->required(),

            Forms\Components\Select::make('status')
                ->label('Trạng thái')
                ->options(self::enumOptions(CommissionPaymentStatus::class))
                ->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('referrer.name')
                    ->label('Nhân viên')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('referralHistory.name')
                    ->label('Người được giới thiệu'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Số điểm')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Trạng thái')
                    ->formatStateUsing(fn ($state) => $state instanceof CommissionPaymentStatus ? $state->label() : CommissionPaymentStatus::tryFrom((int) $state)?->label())
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Thời gian')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(self::enumOptions(CommissionPaymentStatus::class)),
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
            'index'  => Pages\ListReferralCommissions::route('/'),
            'create' => Pages\CreateReferralCommission::route('/create'),
            'edit'   => Pages\EditReferralCommission::route('/{record}/edit'),
        ];
    }

    private static function enumOptions(string $enum): array
    {
        return collect($enum::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->all();
    }
}
