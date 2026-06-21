<?php

namespace App\Filament\Resources\CourseResource\RelationManagers;

use App\Modules\Learning\Models\CourseEnrollment;
use App\Modules\Learning\Models\Enums\CourseEnrollmentStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';
    protected static ?string $title = 'Tiến độ học';
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
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        $isRequiredCourse = $this->getOwnerRecord()->is_required ?? false;

        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Nhân viên')->searchable(),
                Tables\Columns\TextColumn::make('user.department')->label('Phòng ban'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Trạng thái')
                    ->formatStateUsing(fn ($state) => $state instanceof CourseEnrollmentStatus ? $state->label() : CourseEnrollmentStatus::tryFrom((int) $state)?->label())
                    ->badge()
                    ->color(function ($state): string {
                        $status = $state instanceof CourseEnrollmentStatus
                            ? $state
                            : CourseEnrollmentStatus::tryFrom((int) $state);
                        return match ($status) {
                            CourseEnrollmentStatus::NOT_STARTED        => 'gray',
                            CourseEnrollmentStatus::IN_PROGRESS        => 'warning',
                            CourseEnrollmentStatus::PENDING_GRADING    => 'info',
                            CourseEnrollmentStatus::PENDING_ONBOARDING => 'primary',
                            CourseEnrollmentStatus::COMPLETED          => 'success',
                            default                                    => 'gray',
                        };
                    }),
                Tables\Columns\TextColumn::make('progress_percent')->label('Tiến độ')->suffix('%')->sortable(),
                Tables\Columns\TextColumn::make('quiz_status')
                    ->label('Quiz')
                    ->getStateUsing(function (CourseEnrollment $record): string {
                        if ($record->quiz_status === 'in_progress') {
                            return 'Đang làm';
                        }
                        return match ($record->status) {
                            CourseEnrollmentStatus::PENDING_GRADING,
                            CourseEnrollmentStatus::PENDING_ONBOARDING,
                            CourseEnrollmentStatus::COMPLETED => 'Đã nộp',
                            default => 'Chưa làm',
                        };
                    })
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Đang làm' => 'warning',
                        'Đã nộp'   => 'success',
                        default    => 'gray',
                    }),
                Tables\Columns\TextColumn::make('quiz_score')
                    ->label('Điểm')
                    ->getStateUsing(fn ($record) => $this->calculateQuizScore($record))
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format($state, 2) . '/10' : '—')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state === null => 'gray',
                        $state >= 8.0  => 'success',
                        $state >= 5.0  => 'warning',
                        default        => 'danger',
                    }),
                Tables\Columns\TextColumn::make('completed_at')->label('Hoàn thành')->dateTime('d/m/Y H:i'),
            ])
            ->filters([Tables\Filters\SelectFilter::make('status')->label('Trạng thái')->options($this->enumOptions(CourseEnrollmentStatus::class))])
            ->headerActions([Tables\Actions\CreateAction::make()->label('Gán nhân viên học')])
            ->actions([
                Tables\Actions\Action::make('confirmOnboarding')
                    ->label('Duyệt onboarding')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $isRequiredCourse && $record->status !== CourseEnrollmentStatus::COMPLETED)
                    ->tooltip(function ($record): ?string {
                        $count = $this->countUngradedEssays($record);
                        if ($count > 0) {
                            return "Còn {$count} câu tự luận chưa chấm — vào mục Chấm bài quiz để chấm trước.";
                        }
                        return null;
                    })
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $learningService = app(\App\Modules\Learning\Interfaces\LearningServiceInterface::class);
                        $result = $learningService->adminConfirmOnboarding(
                            (string) $record->course_id,
                            (string) $record->user_id,
                            (string) auth()->user()?->getAuthIdentifier()
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

    private function countUngradedEssays(CourseEnrollment $record): int
    {
        $lessonIds = DB::table('course_lessons')
            ->where('course_id', $record->course_id)
            ->pluck('id');

        if ($lessonIds->isEmpty()) {
            return 0;
        }

        $essayQuizIds = DB::table('course_quizzes')
            ->whereIn('lesson_id', $lessonIds)
            ->where('type', 'essay')
            ->whereNull('deleted_at')
            ->pluck('id');

        if ($essayQuizIds->isEmpty()) {
            return 0;
        }

        return DB::table('quiz_attempts')
            ->where('user_id', $record->user_id)
            ->whereIn('quiz_id', $essayQuizIds)
            ->where('is_draft', false)
            ->whereNull('is_correct')
            ->count();
    }

    private function calculateQuizScore(CourseEnrollment $record): ?float
    {
        $lessonIds = DB::table('course_lessons')
            ->where('course_id', $record->course_id)
            ->pluck('id');

        if ($lessonIds->isEmpty()) {
            return null;
        }

        $quizIds = DB::table('course_quizzes')
            ->whereIn('lesson_id', $lessonIds)
            ->whereNull('deleted_at')
            ->pluck('id');

        if ($quizIds->isEmpty()) {
            return null;
        }

        $gradedAttempts = DB::table('quiz_attempts')
            ->where('user_id', $record->user_id)
            ->whereIn('quiz_id', $quizIds)
            ->where('is_draft', false)
            ->whereNotNull('is_correct')
            ->get(['is_correct']);

        if ($gradedAttempts->isEmpty()) {
            return null;
        }

        $totalQuestions = $quizIds->count();
        $correctCount   = $gradedAttempts->where('is_correct', true)->count();

        return round(($correctCount / $totalQuestions) * 10, 2);
    }

    private function enumOptions(string $enum): array
    {
        return collect($enum::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all();
    }
}
