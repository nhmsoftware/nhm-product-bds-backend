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
}
