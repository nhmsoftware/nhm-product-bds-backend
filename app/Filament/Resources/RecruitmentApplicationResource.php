<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecruitmentApplicationResource\Pages;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Modules\Auth\Models\User;
use App\Modules\Recruitment\Models\RecruitmentApplication;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RecruitmentApplicationResource extends Resource
{
    protected static ?string $model = RecruitmentApplication::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationGroup = 'Nhân sự';

    protected static ?string $modelLabel = 'Duyệt đơn ứng tuyển';

    protected static ?string $pluralModelLabel = 'Duyệt đơn ứng tuyển';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Thông tin ứng viên')
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->label('Ứng viên')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->disabled(),

                    Forms\Components\Select::make('applied_position')
                        ->label('Vị trí ứng tuyển')
                        ->options([
                            UserRole::EMPLOYEE->value => 'Nhân viên',
                            UserRole::MANAGER->value => 'Trưởng phòng',
                            UserRole::DIRECTOR->value => 'Giám đốc',
                        ])
                        ->required()
                        ->disabled(),

                    Forms\Components\Select::make('applied_branch_id')
                        ->label('Chi nhánh ứng tuyển')
                        ->relationship('appliedBranch', 'name')
                        ->required()
                        ->disabled(),

                    Forms\Components\Select::make('status')
                        ->label('Trạng thái')
                        ->options([
                            'pending' => 'Chờ duyệt',
                            'approved' => 'Đã duyệt',
                            'rejected' => 'Từ chối',
                        ])
                        ->required()
                        ->disabled(),
                ])->columns(2),

            Forms\Components\Section::make('Hồ sơ ứng tuyển')
                ->schema([
                    Forms\Components\TextInput::make('cv_url')
                        ->label('Link CV / Đính kèm')
                        ->disabled(),

                    Forms\Components\TextInput::make('profile_url')
                        ->label('Link Profile')
                        ->disabled(),

                    Forms\Components\TextInput::make('education')
                        ->label('Học vấn')
                        ->disabled(),

                    Forms\Components\TextInput::make('experience')
                        ->label('Kinh nghiệm')
                        ->disabled(),

                    Forms\Components\Textarea::make('introduction')
                        ->label('Giới thiệu bản thân')
                        ->rows(4)
                        ->disabled()
                        ->columnSpanFull(),
                ])->columns(2),

            Forms\Components\Section::make('Thông tin duyệt đơn')
                ->schema([
                    Forms\Components\Select::make('approved_by')
                        ->label('Người xử lý')
                        ->relationship('approver', 'name')
                        ->disabled(),

                    Forms\Components\DateTimePicker::make('processed_at')
                        ->label('Thời gian xử lý')
                        ->disabled(),

                    Forms\Components\Textarea::make('rejected_reason')
                        ->label('Lý do từ chối')
                        ->rows(3)
                        ->disabled()
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Họ và tên')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('appliedBranch.name')
                    ->label('Chi nhánh ứng tuyển')
                    ->sortable(),

                Tables\Columns\TextColumn::make('applied_position')
                    ->label('Vị trí ứng tuyển')
                    ->formatStateUsing(fn ($state) => $state instanceof UserRole ? $state->label() : (UserRole::tryFrom((int)$state)?->label() ?? '—')),

                Tables\Columns\TextColumn::make('cv_url')
                    ->label('CV / Tài liệu')
                    ->formatStateUsing(fn ($state) => $state ? 'Xem CV' : 'Không có')
                    ->url(fn ($state) => $state ? asset('storage/' . $state) : null, true)
                    ->color(fn ($state) => $state ? 'primary' : 'gray'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Chờ duyệt',
                        'approved' => 'Đã duyệt',
                        'rejected' => 'Từ chối',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('processed_at')
                    ->label('Thời gian duyệt')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Phê duyệt')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (RecruitmentApplication $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (RecruitmentApplication $record): void {
                        $record->update([
                            'status' => 'approved',
                            'approved_by' => auth()->id(),
                            'processed_at' => now(),
                        ]);

                        // Update the user's role and branch
                        $user = $record->user;
                        if ($user) {
                            $user->role = $record->applied_position;
                            $user->branch_id = $record->applied_branch_id;
                            $user->save();
                        }

                        Notification::make()
                            ->title('Duyệt đơn ứng tuyển thành công')
                            ->body('Vai trò và chi nhánh của nhân viên đã được cập nhật.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Từ chối')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (RecruitmentApplication $record): bool => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('rejected_reason')
                            ->label('Lý do từ chối')
                            ->required()
                            ->validationMessages([
                                'required' => 'Vui lòng nhập lý do từ chối.',
                            ]),
                    ])
                    ->action(function (RecruitmentApplication $record, array $data): void {
                        $record->update([
                            'status' => 'rejected',
                            'rejected_reason' => $data['rejected_reason'],
                            'approved_by' => auth()->id(),
                            'processed_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Đã từ chối đơn ứng tuyển')
                            ->danger()
                            ->send();
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();
        if (!$user) {
            return $query;
        }

        if ($user->role === UserRole::DIRECTOR && $user->branch_id) {
            return $query->where('applied_branch_id', $user->branch_id);
        }

        if ($user->role === UserRole::MANAGER) {
            return $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecruitmentApplications::route('/'),
            'create' => Pages\CreateRecruitmentApplication::route('/create'),
            'edit' => Pages\EditRecruitmentApplication::route('/{record}/edit'),
        ];
    }
}
