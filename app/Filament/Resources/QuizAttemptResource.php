<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\QuizAttemptResource\Pages\GradeAttempts;
use App\Filament\Resources\QuizAttemptResource\Pages\ListQuizAttempts;
use App\Modules\Learning\Models\QuizAttempt;
use App\Modules\Learning\Models\CourseLesson;
use App\Modules\Learning\Models\CourseQuiz;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
        // Loại bỏ các câu hỏi hoặc bài học đã bị xóa mềm (soft deleted)
        $representativeIds = QuizAttempt::query()
            ->join('course_quizzes as cq', 'cq.id', '=', 'quiz_attempts.quiz_id')
            ->join('course_lessons as cl', 'cl.id', '=', 'cq.lesson_id')
            ->whereNull('cq.deleted_at')
            ->whereNull('cl.deleted_at')
            ->where('quiz_attempts.is_draft', false)
            ->groupBy('quiz_attempts.user_id', 'cl.course_id')
            ->selectRaw('MIN(quiz_attempts.id::text) as id')
            ->pluck('id');

        return parent::getEloquentQuery()
            ->with(['user', 'quiz.lesson.course'])
            ->whereIn('id', $representativeIds);
    }

    /**
     * Kiểm tra user có câu tự luận nào chưa chấm trong khóa học cho trước không.
     */
    private static function hasPendingEssayGrading(QuizAttempt $record): bool
    {
        $quiz = $record->quiz;
        if (!$quiz || !$quiz->lesson) {
            return false;
        }

        $courseId = $quiz->lesson->course_id;
        return QuizAttempt::query()
            ->join('course_quizzes as cq', 'cq.id', '=', 'quiz_attempts.quiz_id')
            ->join('course_lessons as cl', 'cl.id', '=', 'cq.lesson_id')
            ->where('cl.course_id', $courseId)
            ->where('quiz_attempts.user_id', $record->user_id)
            ->where('quiz_attempts.is_draft', false)
            ->where('cq.type', 'essay')
            ->whereNull('quiz_attempts.is_correct')
            ->exists();
    }

    /**
     * Tính điểm quiz cho user trong khóa học cho trước.
     */
    private static function computeScore(QuizAttempt $record): array
    {
        $quiz = $record->quiz;
        if (!$quiz || !$quiz->lesson) {
            return ['total' => 0, 'correct' => 0];
        }

        $courseId = $quiz->lesson->course_id;
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
        return ['total' => $totalCount, 'correct' => $correctCount];
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
                        $quiz = $record->quiz;
                        if (!$quiz || !$quiz->lesson) return 0;
                        $courseId = $quiz->lesson->course_id;
                        $lessonIds = CourseLesson::query()->where('course_id', $courseId)->pluck('id');
                        if ($lessonIds->isEmpty()) return 0;

                        $essayQuizIds = CourseQuiz::query()
                            ->whereIn('lesson_id', $lessonIds)
                            ->where('type', 'essay')
                            ->pluck('id');
                        if ($essayQuizIds->isEmpty()) return 0;

                        return QuizAttempt::query()
                            ->where('user_id', $record->user_id)
                            ->whereIn('quiz_id', $essayQuizIds)
                            ->where('is_draft', false)
                            ->whereNull('is_correct')
                            ->count();
                    })
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Trạng thái')
                    ->getStateUsing(fn (QuizAttempt $record): string => self::hasPendingEssayGrading($record) ? 'Chờ chấm' : 'Đã chấm')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Chờ chấm' ? 'warning' : 'success'),

                Tables\Columns\TextColumn::make('correct_answers')
                    ->label('Số câu đúng')
                    ->alignCenter()
                    ->getStateUsing(function (QuizAttempt $record): string {
                        $score = self::computeScore($record);
                        return "{$score['correct']}/{$score['total']}";
                    }),

                Tables\Columns\TextColumn::make('score')
                    ->label('Điểm')
                    ->getStateUsing(function (QuizAttempt $record): string {
                        $score = self::computeScore($record);
                        $points = $score['total'] > 0 ? round(($score['correct'] / $score['total']) * 10, 1) : 0;
                        return "{$points} / 10";
                    })
                    ->badge()
                    ->color(fn (string $state): string => (float)explode(' ', $state)[0] >= 8.0 ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ngày nộp')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'asc')
            ->actionsPosition(ActionsPosition::BeforeColumns)
            ->actionsAlignment('left')
            ->actions([
                Tables\Actions\Action::make('grade')
                    ->label(fn (QuizAttempt $record): string => self::hasPendingEssayGrading($record) ? 'Chấm bài' : 'Xem chi tiết')
                    ->icon(fn (QuizAttempt $record): string => self::hasPendingEssayGrading($record) ? 'heroicon-o-pencil' : 'heroicon-o-eye')
                    ->color(fn (QuizAttempt $record): string => self::hasPendingEssayGrading($record) ? 'primary' : 'gray')
                    ->url(fn (QuizAttempt $record): string => self::getUrl('grade', ['record' => $record->id])),
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
