<?php

use App\Modules\Learning\Http\Controllers\LearningController;
use App\Modules\Learning\Http\Controllers\LearningAdminController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Learning Module Routes
|--------------------------------------------------------------------------
|
| UC-053: View Mandatory Course
|
*/

Route::middleware('auth:api')->prefix('v1/learning')->group(function () {
    // Xem danh sách khóa học bắt buộc của nhân viên (UC-053)
    Route::get('/courses', [LearningController::class, 'index'])->name('learning.courses.index');

    // Xem chi tiết khóa học bắt buộc và các bài học (UC-053)
    Route::get('/courses/{id}', [LearningController::class, 'show'])->name('learning.courses.show');

    // Ghi nhận hoàn thành khóa học (UC-057)
    Route::post('/courses/{id}/complete', [LearningController::class, 'complete'])->name('learning.courses.complete');

    // Xem thông tin chứng nhận hoàn thành khóa học (UC-058)
    Route::get('/courses/{id}/certificate', [LearningController::class, 'getCertificate'])->name('learning.courses.certificate');

    // Tải file chứng nhận hoàn thành khóa học (UC-058)
    Route::get('/courses/{id}/certificate/download', [LearningController::class, 'downloadCertificate'])->name('learning.courses.certificate.download');

    // Xem chi tiết bài học (UC-054)
    Route::get('/lessons/{id}', [LearningController::class, 'showLesson'])->name('learning.lessons.show');

    // Cập nhật tiến độ xem video bài học (UC-055)
    Route::post('/lessons/{id}/progress', [LearningController::class, 'updateProgress'])->name('learning.lessons.progress');

    // Lấy câu hỏi kiểm tra (UC-056)
    Route::get('/lessons/{id}/quiz', [LearningController::class, 'getQuiz'])->name('learning.lessons.quiz');

    // Nộp bài kiểm tra trắc nghiệm (UC-056)
    Route::post('/lessons/{id}/quiz/submit', [LearningController::class, 'submitQuiz'])->name('learning.lessons.quiz.submit');

    // Lưu tạm bài làm quiz (lưu bản nháp) (UC-059)
    Route::post('/lessons/{id}/quiz/draft', [LearningController::class, 'saveDraft'])->name('learning.lessons.quiz.draft');
});

Route::middleware('auth:api')->prefix('v1/learning/admin')->group(function () {
    // Quản lý khóa học
    Route::get('/courses', [LearningAdminController::class, 'getCourses'])->name('learning.admin.courses.index');
    Route::get('/courses/{id}', [LearningAdminController::class, 'getCourseDetails'])->name('learning.admin.courses.show');
    Route::post('/courses', [LearningAdminController::class, 'createCourse'])->name('learning.admin.courses.store');
    Route::put('/courses/{id}', [LearningAdminController::class, 'updateCourse'])->name('learning.admin.courses.update');
    Route::delete('/courses/{id}', [LearningAdminController::class, 'deleteCourse'])->name('learning.admin.courses.destroy');

    // Quản lý bài học
    Route::post('/lessons', [LearningAdminController::class, 'createLesson'])->name('learning.admin.lessons.store');
    Route::put('/lessons/{id}', [LearningAdminController::class, 'updateLesson'])->name('learning.admin.lessons.update');
    Route::delete('/lessons/{id}', [LearningAdminController::class, 'deleteLesson'])->name('learning.admin.lessons.destroy');

    // Quản lý bài thi/quiz
    Route::post('/quizzes', [LearningAdminController::class, 'createQuiz'])->name('learning.admin.quizzes.store');
    Route::put('/quizzes/{id}', [LearningAdminController::class, 'updateQuiz'])->name('learning.admin.quizzes.update');
    Route::delete('/quizzes/{id}', [LearningAdminController::class, 'deleteQuiz'])->name('learning.admin.quizzes.destroy');

    // Xác nhận onboarding
    Route::post('/courses/{courseId}/enrollments/{userId}/complete', [LearningAdminController::class, 'confirmOnboarding'])->name('learning.admin.courses.confirm-onboarding');
});
