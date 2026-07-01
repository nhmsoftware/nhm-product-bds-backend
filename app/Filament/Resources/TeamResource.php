<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeamResource\Pages;
use App\Modules\Auth\Models\Team;
use App\Modules\Auth\Models\User;
use App\Modules\Auth\Models\Department;
use App\Modules\Branch\Models\Branch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Nhân sự';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Đội nhóm';

    protected static ?string $pluralModelLabel = 'Quản lý đội nhóm';

    protected static ?string $navigationLabel = 'Quản lý đội nhóm';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('branch_id')
                    ->label('Chi nhánh')
                    ->options(fn () => Branch::where('is_active', true)->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required()
                    ->dehydrated(false)
                    ->afterStateHydrated(function (Forms\Components\Select $component, ?Team $record) {
                        if ($record && $record->department) {
                            $component->state($record->department->branch_id);
                        }
                    })
                    ->placeholder('Chọn chi nhánh'),
                Forms\Components\Select::make('department_id')
                    ->label('Phòng ban')
                    ->options(function (Forms\Get $get) {
                        $branchId = $get('branch_id');
                        if (!$branchId) {
                            return [];
                        }
                        return Department::where('branch_id', $branchId)
                            ->where('is_active', true)
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->disabled(fn (Forms\Get $get): bool => !$get('branch_id'))
                    ->placeholder('Chọn phòng ban'),
                Forms\Components\TextInput::make('name')
                    ->label('Tên đội nhóm')
                    ->required()
                    ->maxLength(255)
                    ->rules(function (Forms\Get $get): array {
                        $departmentId = $get('department_id');
                        return [
                            \Illuminate\Validation\Rule::unique('teams', 'name')
                                ->where(fn ($query) => $query->where('department_id', $departmentId))
                                ->ignore($get('id')),
                        ];
                    })
                    ->validationMessages(['unique' => 'Tên đội nhóm đã tồn tại trong phòng ban này.'])
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Forms\Set $set, ?string $state) {
                        $code = \Illuminate\Support\Str::slug($state ?? '');
                        $set('code', $code);
                    }),
                Forms\Components\TextInput::make('code')
                    ->label('Mã đội nhóm')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),
                Forms\Components\Select::make('leader_id')
                    ->label('Trưởng nhóm')
                    ->options(function (Forms\Get $get) {
                        $departmentId = $get('department_id');
                        if (!$departmentId) {
                            return [];
                        }
                        return User::where('department_id', $departmentId)
                            ->where('is_active', true)
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->disabled(fn (Forms\Get $get): bool => !$get('department_id'))
                    ->placeholder('Chọn trưởng nhóm'),
                Forms\Components\Toggle::make('is_active')
                    ->label('Đang hoạt động')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Tên đội nhóm')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('Mã')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('department.branch.name')
                    ->label('Chi nhánh')
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('department.name')
                    ->label('Phòng ban')
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('leader.name')
                    ->label('Trưởng nhóm')
                    ->placeholder('Chưa phân công')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Hoạt động')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('delete')
                    ->label('Xóa')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Xóa đội nhóm')
                    ->modalSubmitActionLabel('Xác nhận xóa')
                    ->action(function (Team $record): void {
                        if ($record->leader_id) {
                            Notification::make()
                                ->title('Không thể xóa đội nhóm')
                                ->body('Đội nhóm đang có Trưởng nhóm được phân công. Vui lòng gỡ Trưởng nhóm trước khi xóa.')
                                ->danger()
                                ->send();
                            return;
                        }

                        if (User::where('team_id', $record->id)->exists()) {
                            Notification::make()
                                ->title('Không thể xóa đội nhóm')
                                ->body('Đội nhóm đang có nhân sự trực thuộc. Vui lòng chuyển nhân sự trước khi xóa.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $record->delete();

                        Notification::make()
                            ->title('Xóa đội nhóm thành công')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('delete')
                        ->label('Xóa đã chọn')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $skipped = [];

                            foreach ($records as $record) {
                                if ($record->leader_id) {
                                    $skipped[] = "「{$record->name}」đang có Trưởng nhóm";
                                    continue;
                                }
                                if (User::where('team_id', $record->id)->exists()) {
                                    $skipped[] = "「{$record->name}」đang có nhân sự trực thuộc";
                                    continue;
                                }
                                $record->delete();
                            }

                            if (!empty($skipped)) {
                                Notification::make()
                                    ->title('Một số đội nhóm không thể xóa')
                                    ->body(implode("\n", $skipped))
                                    ->danger()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Xóa đội nhóm thành công')
                                    ->success()
                                    ->send();
                            }
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeams::route('/'),
            'create' => Pages\CreateTeam::route('/create'),
            'edit' => Pages\EditTeam::route('/{record}/edit'),
        ];
    }
}
