<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\QuizAttemptResource\Pages\GradeAttempts;
use App\Filament\Resources\QuizAttemptResource\Pages\ListQuizAttempts;
use App\Modules\Learning\Models\QuizAttempt;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class QuizAttemptResource extends Resource
{
    protected static ?string $model = QuizAttempt::class;
    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';
    protected static ?string $navigationGroup = 'Đào tạo';
    protected static ?string $navigationLabel = 'Chấm bài quiz';
    protected static ?string $modelLabel = 'Bài nộp';
    protected static ?string $pluralModelLabel = 'Chấm bài quiz';

    public static function getEloquentQuery(): Builder
    {
        // 1 row per user-course — lấy attempt đại diện (MIN id) của mỗi user đã nộp (không nháp) cho từng khóa học
        $representativeIds = DB::table('quiz_attempts as qa')
            ->join('course_quizzes as cq', 'cq.id', '=', 'qa.quiz_id')
            ->join('course_lessons as cl', 'cl.id', '=', 'cq.lesson_id')
            ->where('qa.is_draft', false)
            ->groupBy('qa.user_id', 'cl.course_id')
            ->selectRaw('MIN(qa.id::text) as id')
            ->pluck('id');

        return parent::getEloquentQuery()
            ->with(['user', 'quiz.lesson.course'])
            ->whereIn('id', $representativeIds);
    }

    public static function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nhân viên')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('quiz.lesson.course.title')
                    ->label('Khóa học')
                    ->limit(40),

                Tables\Columns\TextColumn::make('pending_count')
                    ->label('Câu chờ chấm')
                    ->alignCenter()
                    ->getStateUsing(function (QuizAttempt $record): int {
                        $courseId = $record->quiz->lesson->course_id;
                        return QuizAttempt::query()
                            ->join('course_quizzes as cq', 'cq.id', '=', 'quiz_attempts.quiz_id')
                            ->join('course_lessons as cl', 'cl.id', '=', 'cq.lesson_id')
                            ->where('cl.course_id', $courseId)
                            ->where('quiz_attempts.user_id', $record->user_id)
                            ->where('quiz_attempts.is_draft', false)
                            ->where('cq.type', 'essay')
                            ->whereNull('quiz_attempts.is_correct')
                            ->count();
                    })
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Trạng thái')
                    ->getStateUsing(function (QuizAttempt $record): string {
                        $courseId = $record->quiz->lesson->course_id;
                        $hasPending = QuizAttempt::query()
                            ->join('course_quizzes as cq', 'cq.id', '=', 'quiz_attempts.quiz_id')
                            ->join('course_lessons as cl', 'cl.id', '=', 'cq.lesson_id')
                            ->where('cl.course_id', $courseId)
                            ->where('quiz_attempts.user_id', $record->user_id)
                            ->where('quiz_attempts.is_draft', false)
                            ->where('cq.type', 'essay')
                            ->whereNull('quiz_attempts.is_correct')
                            ->exists();
                        return $hasPending ? 'Chờ chấm' : 'Đã chấm';
                    })
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Chờ chấm' ? 'warning' : 'success'),

                Tables\Columns\TextColumn::make('correct_answers')
                    ->label('Số câu đúng')
                    ->alignCenter()
                    ->getStateUsing(function (QuizAttempt $record): string {
                        $courseId = $record->quiz->lesson->course_id;
                        $allAttempts = QuizAttempt::query()
                            ->join('course_quizzes as cq', 'cq.id', '=', 'quiz_attempts.quiz_id')
                            ->join('course_lessons as cl', 'cl.id', '=', 'cq.lesson_id')
                            ->where('cl.course_id', $courseId)
                            ->where('quiz_attempts.user_id', $record->user_id)
                            ->where('quiz_attempts.is_draft', false)
                            ->select('quiz_attempts.is_correct')
                            ->get();

                        $totalCount = $allAttempts->count();
                        $correctCount = $allAttempts->where('is_correct', true)->count();

                        return "{$correctCount}/{$totalCount}";
                    }),

                Tables\Columns\TextColumn::make('score')
                    ->label('Điểm')
                    ->getStateUsing(function (QuizAttempt $record): string {
                        $courseId = $record->quiz->lesson->course_id;
                        $allAttempts = QuizAttempt::query()
                            ->join('course_quizzes as cq', 'cq.id', '=', 'quiz_attempts.quiz_id')
                            ->join('course_lessons as cl', 'cl.id', '=', 'cq.lesson_id')
                            ->where('cl.course_id', $courseId)
                            ->where('quiz_attempts.user_id', $record->user_id)
                            ->where('quiz_attempts.is_draft', false)
                            ->select('quiz_attempts.is_correct')
                            ->get();

                        $totalCount = $allAttempts->count();
                        $correctCount = $allAttempts->where('is_correct', true)->count();

                        $score = $totalCount > 0 ? round(($correctCount / $totalCount) * 10, 1) : 0;
                        return "{$score} / 10";
                    })
                    ->badge()
                    ->color(fn (string $state): string => (float)explode(' ', $state)[0] >= 8.0 ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ngày nộp')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'asc')
            ->actions([
                Tables\Actions\Action::make('grade')
                    ->label(function (QuizAttempt $record): string {
                        $courseId = $record->quiz->lesson->course_id;
                        $hasPending = QuizAttempt::query()
                            ->join('course_quizzes as cq', 'cq.id', '=', 'quiz_attempts.quiz_id')
                            ->join('course_lessons as cl', 'cl.id', '=', 'cq.lesson_id')
                            ->where('cl.course_id', $courseId)
                            ->where('quiz_attempts.user_id', $record->user_id)
                            ->where('quiz_attempts.is_draft', false)
                            ->where('cq.type', 'essay')
                            ->whereNull('quiz_attempts.is_correct')
                            ->exists();
                        return $hasPending ? 'Chấm bài' : 'Xem chi tiết';
                    })
                    ->icon(function (QuizAttempt $record): string {
                        $courseId = $record->quiz->lesson->course_id;
                        $hasPending = QuizAttempt::query()
                            ->join('course_quizzes as cq', 'cq.id', '=', 'quiz_attempts.quiz_id')
                            ->join('course_lessons as cl', 'cl.id', '=', 'cq.lesson_id')
                            ->where('cl.course_id', $courseId)
                            ->where('quiz_attempts.user_id', $record->user_id)
                            ->where('quiz_attempts.is_draft', false)
                            ->where('cq.type', 'essay')
                            ->whereNull('quiz_attempts.is_correct')
                            ->exists();
                        return $hasPending ? 'heroicon-o-pencil' : 'heroicon-o-eye';
                    })
                    ->color(function (QuizAttempt $record): string {
                        $courseId = $record->quiz->lesson->course_id;
                        $hasPending = QuizAttempt::query()
                            ->join('course_quizzes as cq', 'cq.id', '=', 'quiz_attempts.quiz_id')
                            ->join('course_lessons as cl', 'cl.id', '=', 'cq.lesson_id')
                            ->where('cl.course_id', $courseId)
                            ->where('quiz_attempts.user_id', $record->user_id)
                            ->where('quiz_attempts.is_draft', false)
                            ->where('cq.type', 'essay')
                            ->whereNull('quiz_attempts.is_correct')
                            ->exists();
                        return $hasPending ? 'primary' : 'gray';
                    })
                    ->url(fn (QuizAttempt $record): string => self::getUrl('grade', ['record' => $record->id])),
                Tables\Actions\Action::make('confirmOnboarding')
                    ->label('Duyệt onboarding')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(function (QuizAttempt $record): bool {
                        $course = $record->quiz?->lesson?->course;
                        if (!$course || !$course->is_required) {
                            return false;
                        }

                        // Check if status is pending onboarding
                        $enrollment = \App\Modules\Learning\Models\CourseEnrollment::where('user_id', $record->user_id)
                            ->where('course_id', $course->id)
                            ->first();

                        return $enrollment && $enrollment->status === \App\Modules\Learning\Models\Enums\CourseEnrollmentStatus::PENDING_ONBOARDING;
                    })
                    ->requiresConfirmation()
                    ->action(function (QuizAttempt $record) {
                        $courseId = $record->quiz->lesson->course_id;
                        $learningService = app(\App\Modules\Learning\Interfaces\LearningServiceInterface::class);
                        $result = $learningService->adminConfirmOnboarding(
                            (string) $courseId,
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
            'index' => ListQuizAttempts::route('/'),
            'grade' => GradeAttempts::route('/{record}/grade'),
        ];
    }
}
