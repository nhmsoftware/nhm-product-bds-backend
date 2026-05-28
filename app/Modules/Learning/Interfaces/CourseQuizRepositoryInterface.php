<?php

namespace App\Modules\Learning\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;

interface CourseQuizRepositoryInterface extends BaseRepositoryInterface
{
    public function getByLessonId(string $lessonId): \Illuminate\Database\Eloquent\Collection;

    public function getByLessonIds(iterable $lessonIds): \Illuminate\Database\Eloquent\Collection;

    public function countByLessonIds(iterable $lessonIds): int;

    public function deleteByIds(array $ids): int;
}
