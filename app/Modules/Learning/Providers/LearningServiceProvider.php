<?php

namespace App\Modules\Learning\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\Learning\Interfaces\CourseEnrollmentRepositoryInterface;
use App\Modules\Learning\Interfaces\CourseRepositoryInterface;
use App\Modules\Learning\Interfaces\CourseLessonRepositoryInterface;
use App\Modules\Learning\Interfaces\CourseQuizRepositoryInterface;
use App\Modules\Learning\Interfaces\LearningServiceInterface;
use App\Modules\Learning\Interfaces\QuizAttemptRepositoryInterface;
use App\Modules\Learning\Repositories\CourseEnrollmentRepository;
use App\Modules\Learning\Repositories\CourseRepository;
use App\Modules\Learning\Repositories\CourseLessonRepository;
use App\Modules\Learning\Repositories\CourseQuizRepository;
use App\Modules\Learning\Repositories\QuizAttemptRepository;
use App\Modules\Learning\Services\LearningService;

/**
 * Class LearningServiceProvider
 *
 * Provider đăng ký các cấu hình, routes, repositories và services cho Module Learning.
 *
 * @package App\Modules\Learning\Providers
 */
class LearningServiceProvider extends BaseModuleServiceProvider
{
    /**
     * Trả về tên Module để đăng ký tự động.
     *
     * @return string
     */
    protected function getModuleName(): string
    {
        return 'Learning';
    }

    /**
     * Đăng ký các singleton bindings cho Module Learning.
     *
     * @return void
     */
    public function register(): void
    {
        parent::register();

        $this->app->singleton(CourseRepositoryInterface::class, CourseRepository::class);
        $this->app->singleton(CourseEnrollmentRepositoryInterface::class, CourseEnrollmentRepository::class);
        $this->app->singleton(CourseLessonRepositoryInterface::class, CourseLessonRepository::class);
        $this->app->singleton(CourseQuizRepositoryInterface::class, CourseQuizRepository::class);
        $this->app->singleton(QuizAttemptRepositoryInterface::class, QuizAttemptRepository::class);
        $this->app->singleton(LearningServiceInterface::class, LearningService::class);
    }

    /**
     * Thực thi các thiết lập boot của service provider.
     *
     * @return void
     */
    public function boot(): void
    {
        parent::boot();
    }
}
