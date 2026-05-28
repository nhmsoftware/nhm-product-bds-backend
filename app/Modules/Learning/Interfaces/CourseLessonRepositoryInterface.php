<?php

namespace App\Modules\Learning\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;

interface CourseLessonRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Lấy các bài học của khóa học.
     */
    public function getByCourseId(string $courseId): \Illuminate\Database\Eloquent\Collection;
}
