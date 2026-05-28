<?php

namespace App\Modules\Learning\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;

interface CourseQuizRepositoryInterface extends BaseRepositoryInterface
{
    public function getByLessonId(string $lessonId): \Illuminate\Database\Eloquent\Collection;
}
