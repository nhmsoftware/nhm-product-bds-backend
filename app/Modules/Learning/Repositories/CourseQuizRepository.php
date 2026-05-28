<?php

namespace App\Modules\Learning\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Learning\Interfaces\CourseQuizRepositoryInterface;
use App\Modules\Learning\Models\CourseQuiz;

final class CourseQuizRepository extends BaseRepository implements CourseQuizRepositoryInterface
{
    public function getModel(): string
    {
        return CourseQuiz::class;
    }

    public function getByLessonId(string $lessonId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->where('lesson_id', $lessonId)->get();
    }
}
