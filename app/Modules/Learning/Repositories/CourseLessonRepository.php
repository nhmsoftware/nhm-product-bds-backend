<?php

namespace App\Modules\Learning\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Learning\Interfaces\CourseLessonRepositoryInterface;
use App\Modules\Learning\Models\CourseLesson;

final class CourseLessonRepository extends BaseRepository implements CourseLessonRepositoryInterface
{
    public function getModel(): string
    {
        return CourseLesson::class;
    }

    public function getByCourseId(string $courseId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->where('course_id', $courseId)->get();
    }
}
