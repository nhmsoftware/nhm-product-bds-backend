<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DepartmentResource\Pages;
use App\Modules\Auth\Models\Department;
use App\Modules\Auth\Models\User;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Modules\Branch\Models\Branch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Nhân sự';

    protected static ?string $modelLabel = 'Phòng ban';

    protected static ?string $pluralModelLabel = 'Quản lý phòng ban';

    protected static ?string $navigationLabel = 'Quản lý phòng ban';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Tên phòng ban')
                    ->required()
                    ->maxLength(255)
                    ->rules(function (Forms\Get $get): array {
                        $branchId = $get('branch_id');
                        return [
                            Rule::unique('departments', 'name')
                                ->where(fn ($query) => $query->where('branch_id', $branchId))
                                ->ignore($get('id')),
                        ];
                    })
                    ->validationMessages(['unique' => 'Tên phòng ban đã tồn tại trong chi nhánh này.'])
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Forms\Set $set, ?string $state) {
                        $code = \Illuminate\Support\Str::slug($state ?? '');
                        $set('code', $code);
                    }),
                Forms\Components\TextInput::make('code')
                    ->label('Mã phòng ban')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),
                Forms\Components\Select::make('manager_id')
                    ->label('Trưởng phòng')
                    ->options(function () {
                        $currentUser = auth()->user();
                        $query = User::query()
                            ->where('is_active', true)
                            ->where('role', UserRole::MANAGER->value)
                            ->whereNotNull('job_position_id');
                        if ($currentUser && $currentUser->role === UserRole::DIRECTOR && $currentUser->branch_id) {
                            $query->where('branch_id', $currentUser->branch_id);
                        }
                        return $query->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Forms\Components\TextInput::make('kpi_quota')
                    ->label('Định mức KPI phòng')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->required()
                    ->validationMessages(['min_value' => 'Định mức KPI không hợp lệ.']),
                Forms\Components\Select::make('branch_id')
                    ->label('Chi nhánh')
                    ->options(fn () => Branch::where('is_active', true)->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->placeholder('Chọn chi nhánh'),
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
                    ->label('Tên phòng ban')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Chi nhánh')
                    ->placeholder('Chưa phân')
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('Mã')
                    ->searchable(),
                Tables\Columns\TextColumn::make('manager.name')
                    ->label('Trưởng phòng')
                    ->placeholder('Chưa phân công')
                    ->searchable(),
                Tables\Columns\TextColumn::make('kpi_quota')
                    ->label('Định mức KPI')
                    ->numeric()
                    ->sortable(),
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
                    ->modalHeading('Xóa phòng ban')
                    ->modalSubmitActionLabel('Xác nhận xóa')
                    ->action(function (Department $record): void {
                        if ($record->manager_id) {
                            Notification::make()
                                ->title('Không thể xóa phòng ban')
                                ->body('Phòng ban đang có Trưởng phòng được phân công. Vui lòng gỡ/chuyển Trưởng phòng trước khi xóa.')
                                ->danger()
                                ->send();
                            return;
                        }

                        if (User::where('department_id', $record->id)->exists()) {
                            Notification::make()
                                ->title('Không thể xóa phòng ban')
                                ->body('Phòng ban đang có nhân sự trực thuộc. Vui lòng chuyển nhân sự trước khi xóa.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $record->delete();

                        Notification::make()
                            ->title('Xóa phòng ban thành công')
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
                                if ($record->manager_id) {
                                    $skipped[] = "「{$record->name}」đang có Trưởng phòng";
                                    continue;
                                }
                                if (User::where('department_id', $record->id)->exists()) {
                                    $skipped[] = "「{$record->name}」đang có nhân sự trực thuộc";
                                    continue;
                                }
                                $record->delete();
                            }

                            if (!empty($skipped)) {
                                Notification::make()
                                    ->title('Một số phòng ban không thể xóa')
                                    ->body(implode("\n", $skipped))
                                    ->danger()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Xóa phòng ban thành công')
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
            'index' => Pages\ListDepartments::route('/'),
            'create' => Pages\CreateDepartment::route('/create'),
            'edit' => Pages\EditDepartment::route('/{record}/edit'),
        ];
    }
}
