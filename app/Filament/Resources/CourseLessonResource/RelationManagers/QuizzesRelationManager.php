<?php

namespace App\Filament\Resources\CourseLessonResource\RelationManagers;

use App\Filament\Resources\CourseQuizResource;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class QuizzesRelationManager extends RelationManager
{
    protected static string $relationship = 'quizzes';
    protected static ?string $title = 'Quiz và câu hỏi';
    protected static ?string $modelLabel = 'Câu hỏi';
    protected static ?string $pluralModelLabel = 'Câu hỏi';

    public function form(Form $form): Form
    {
        return $form->schema(CourseQuizResource::quizFormSchema($this))->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('question')
            ->columns([
                Tables\Columns\TextColumn::make('lesson.title')->label('Bài học')->limit(30),
                Tables\Columns\TextColumn::make('order')->label('Thứ tự')->sortable(),
                Tables\Columns\TextColumn::make('type')->label('Loại')->formatStateUsing(fn (?string $state) => $state === 'essay' ? 'Tự luận' : 'Trắc nghiệm')->badge(),
                Tables\Columns\TextColumn::make('title')->label('Tiêu đề')->limit(30),
                Tables\Columns\TextColumn::make('question')->label('Câu hỏi')->searchable()->limit(70),
                Tables\Columns\TextColumn::make('correct_option')->label('Đáp án đúng'),
            ])
            ->defaultSort('order')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Thêm câu hỏi')
                    ->before(function (array $data, Tables\Actions\CreateAction $action): void {
                        $courseId = $this->getOwnerRecord()->id;
                        $lessonId = $data['lesson_id'] ?? null;

                        $conflictExists = DB::table('course_quizzes')
                            ->join('course_lessons', 'course_lessons.id', '=', 'course_quizzes.lesson_id')
                            ->where('course_lessons.course_id', $courseId)
                            ->whereNull('course_quizzes.deleted_at')
                            ->when($lessonId, fn ($q) => $q->where('course_quizzes.lesson_id', '!=', $lessonId))
                            ->exists();

                        if ($conflictExists) {
                            Notification::make()
                                ->title('Không thể thêm câu hỏi')
                                ->body('Mỗi khóa học chỉ được có 1 bài học chứa câu hỏi quiz. Vui lòng chọn bài học đang có quiz.')
                                ->danger()
                                ->send();
                            $action->halt();
                        }
                    })
                    ->using(function (array $data, string $model): \Illuminate\Database\Eloquent\Model {
                        return $model::create($data);
                    })
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Sửa câu hỏi'),
                Tables\Actions\DeleteAction::make()->label('Xóa câu hỏi'),
            ]);
    }
}
