<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RewardPointHistoryResource\Pages;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Models\RewardPointHistory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RewardPointHistoryResource extends Resource
{
    protected static ?string $model = RewardPointHistory::class;
    protected static ?string $navigationIcon = 'heroicon-o-star';
    protected static ?string $navigationGroup = 'Nhân sự';
    protected static ?int $navigationSort = 9;
    protected static ?string $modelLabel = 'Lịch sử điểm thưởng';
    protected static ?string $pluralModelLabel = 'Lịch sử điểm thưởng';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')
                ->label('Nhân viên')
                ->relationship('user', 'name', function (Builder $query) {
                    $currentUser = auth()->user();
                    if (!$currentUser) return $query;

                    $query->where('id', '!=', $currentUser->id)
                        ->where('role_id', '!=', \App\Modules\Auth\Models\Role::where('name', 'buyer')->value('id'))
                        ->where('role_id', '!=', \App\Modules\Auth\Models\Role::where('name', 'super_admin')->value('id'))
                        ->whereNotNull('job_position_id');

                    if (!$currentUser->hasAnyPermission(['manage_all', 'manage_activity_history'])) {
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

            Forms\Components\TextInput::make('points_changed')
                ->label('Điểm thay đổi')
                ->numeric()
                ->required(),

            Forms\Components\TextInput::make('related_id')
                ->label('ID liên quan'),

            Forms\Components\Textarea::make('reason')
                ->label('Lý do')
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('user.name')->label('Nhân viên'),
            Tables\Columns\TextColumn::make('points_changed')->label('Điểm'),
            Tables\Columns\TextColumn::make('reason')->label('Lý do')->limit(50),
            Tables\Columns\TextColumn::make('created_at')->label('Ngày')->dateTime('d/m/Y H:i'),
        ])->actions([
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
            'index'  => Pages\ListRewardPointHistorys::route('/'),
            'create' => Pages\CreateRewardPointHistory::route('/create'),
            'edit'   => Pages\EditRewardPointHistory::route('/{record}/edit'),
        ];
    }
}
