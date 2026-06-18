<?php

namespace App\Filament\Resources\CourseResource\RelationManagers;

use App\Modules\Learning\Models\Enums\CourseEnrollmentStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';
    protected static ?string $title = 'Tiến độ học và onboarding';
    protected static ?string $modelLabel = 'Lượt học';
    protected static ?string $pluralModelLabel = 'Lượt học';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')
                ->label('Nhân viên')
                ->relationship('user', 'name', function (\Illuminate\Database\Eloquent\Builder $query) {
                    $currentUser = auth()->user();
                    if (!$currentUser) return $query;
                    $query->where('id', '!=', $currentUser->id)
                          ->where('role', '!=', \App\Modules\Auth\Models\Enums\UserRole::BUYER->value)
                          ->where('role', '!=', \App\Modules\Auth\Models\Enums\UserRole::SUPER_ADMIN->value)
                          ->whereNotNull('job_position_id');
                    if ($currentUser->role !== \App\Modules\Auth\Models\Enums\UserRole::SUPER_ADMIN) {
                        $query->where('role', '<=', $currentUser->role->value);
                    }
                    if ($currentUser->role === \App\Modules\Auth\Models\Enums\UserRole::DIRECTOR && $currentUser->branch_id) {
                        $query->where('branch_id', $currentUser->branch_id);
                    }
                    if ($currentUser->role === \App\Modules\Auth\Models\Enums\UserRole::MANAGER && $currentUser->department_id) {
                        $query->where('department_id', $currentUser->department_id);
                    }
                    return $query;
                })
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Select::make('status')->label('Trạng thái học')->options($this->enumOptions(CourseEnrollmentStatus::class))->required(),
            Forms\Components\TextInput::make('progress_percent')->label('Tiến độ (%)')->numeric()->minValue(0)->maxValue(100)->default(0),
            Forms\Components\DateTimePicker::make('completed_at')->label('Hoàn thành lúc'),
            Forms\Components\TextInput::make('quiz_status')->label('Trạng thái quiz')->helperText('not_started, draft, grading, passed, failed, redo'),
            Forms\Components\TextInput::make('quiz_remaining_seconds')->label('Thời gian quiz còn lại (giây)')->numeric(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Nhân viên')->searchable(),
                Tables\Columns\TextColumn::make('user.department')->label('Phòng ban'),
                Tables\Columns\TextColumn::make('status')->label('Trạng thái')->formatStateUsing(fn ($state) => $state instanceof CourseEnrollmentStatus ? $state->label() : CourseEnrollmentStatus::tryFrom((int) $state)?->label())->badge(),
                Tables\Columns\TextColumn::make('progress_percent')->label('Tiến độ')->suffix('%')->sortable(),
                Tables\Columns\TextColumn::make('quiz_status')->label('Quiz'),
                Tables\Columns\TextColumn::make('completed_at')->label('Hoàn thành')->dateTime('d/m/Y H:i'),
            ])
            ->filters([Tables\Filters\SelectFilter::make('status')->label('Trạng thái')->options($this->enumOptions(CourseEnrollmentStatus::class))])
            ->headerActions([Tables\Actions\CreateAction::make()->label('Gán nhân viên học')])
            ->actions([
                Tables\Actions\Action::make('confirmOnboarding')
                    ->label('Duyệt onboarding')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status !== CourseEnrollmentStatus::COMPLETED)
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $learningService = app(\App\Modules\Learning\Interfaces\LearningServiceInterface::class);
                        $result = $learningService->adminConfirmOnboarding(
                            (string) $record->course_id,
                            (string) $record->user_id,
                            (string) auth()->id()
                        );
                        if ($result->isError()) {
                            \Filament\Notifications\Notification::make()
                                ->title($result->getMessage())
                                ->danger()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Duyệt hoàn thành onboarding cho nhân viên thành công.')
                                ->success()
                                ->send();
                        }
                    }),
                Tables\Actions\EditAction::make()->label('Cập nhật tiến độ'),
                Tables\Actions\DeleteAction::make()->label('Xóa lượt học'),
            ]);
    }

    private function enumOptions(string $enum): array
    {
        return collect($enum::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all();
    }
}
