<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Auth\Models\User;
use App\Modules\Learning\Models\Course;
use App\Modules\Learning\Models\CourseLesson;
use App\Modules\Learning\Models\CourseQuiz;
use App\Modules\Learning\Models\CourseEnrollment;
use App\Modules\Learning\Models\LessonProgress;
use App\Modules\Learning\Models\QuizAttempt;
use App\Modules\Learning\Models\Enums\CourseEnrollmentStatus;
use Illuminate\Support\Collection;

class BdsCourseSeeder extends Seeder
{
    private const REQUIRED_COURSE_TITLE = 'Lộ trình hội nhập nhân viên kinh doanh BĐS';

    private const ESSAY_ANSWER = 'Khi tư vấn khách hàng lần đầu, tôi thực hiện các bước: (1) Chào hỏi, tạo thiện cảm và lắng nghe mục tiêu của khách. (2) Kiểm tra bảng hàng để xác nhận lô đất còn hàng và pháp lý rõ ràng. (3) Trình bày sản phẩm phù hợp với ngân sách và nhu cầu thực tế. (4) Giải đáp băn khoăn và hẹn lịch đi xem thực địa.';

    public function run(): void
    {
        $users = User::query()
            ->whereIn('email', ['employee@test.com', 'employee2@test.com'])
            ->get();

        if ($users->isEmpty()) {
            echo "Không tìm thấy demo nhân viên. Vui lòng chạy InventoryAreaSeeder trước.\n";
            return;
        }

        $course = Course::query()
            ->where('title', self::REQUIRED_COURSE_TITLE)
            ->where('is_required', true)
            ->first();

        if (!$course) {
            echo "Không tìm thấy khóa học bắt buộc. Vui lòng chạy LearningPathDemoSeeder trước.\n";
            return;
        }

        $this->clearExistingAttempts($course, $users->pluck('id')->toArray());

        $lessons = CourseLesson::query()
            ->where('course_id', $course->id)
            ->orderBy('order')
            ->get();

        $quizzesByLesson = CourseQuiz::query()
            ->whereIn('lesson_id', $lessons->pluck('id'))
            ->get()
            ->groupBy('lesson_id');

        echo "Đang seed dữ liệu cho " . $users->count() . " nhân viên demo.\n";

        foreach ($users as $user) {
            $enrollment = CourseEnrollment::create([
                'user_id'          => $user->id,
                'course_id'        => $course->id,
                'status'           => CourseEnrollmentStatus::IN_PROGRESS,
                'progress_percent' => 100.00,
            ]);

            foreach ($lessons as $lesson) {
                LessonProgress::create([
                    'enrollment_id'         => $enrollment->id,
                    'lesson_id'             => $lesson->id,
                    'is_completed'          => true,
                    'current_watch_seconds' => $lesson->duration_seconds ?? 30,
                ]);

                foreach ($quizzesByLesson->get($lesson->id, collect()) as $quiz) {
                    if ($quiz->type === 'multiple_choice') {
                        QuizAttempt::create([
                            'user_id'         => $user->id,
                            'quiz_id'         => $quiz->id,
                            'selected_option' => $quiz->correct_option,
                            'is_correct'      => true,
                            'is_draft'        => false,
                        ]);
                    } else {
                        QuizAttempt::create([
                            'user_id'      => $user->id,
                            'quiz_id'      => $quiz->id,
                            'essay_answer' => self::ESSAY_ANSWER,
                            'is_correct'   => null,
                            'is_draft'     => false,
                        ]);
                    }
                }
            }
        }

        $essayCount = $quizzesByLesson->flatten()->where('type', 'essay')->count() * $users->count();

        echo "Seed BdsCourseSeeder thành công!\n";
        echo "   Khóa học: {$course->title}\n";
        echo "   Chờ chấm: {$essayCount} câu tự luận\n";
    }

    private function clearExistingAttempts(Course $course, array $userIds): void
    {
        $lessonIds = CourseLesson::query()->where('course_id', $course->id)->pluck('id');
        $quizIds   = CourseQuiz::query()->whereIn('lesson_id', $lessonIds)->pluck('id');

        $enrollmentIds = CourseEnrollment::query()
            ->where('course_id', $course->id)
            ->whereIn('user_id', $userIds)
            ->pluck('id');

        QuizAttempt::query()->whereIn('quiz_id', $quizIds)->whereIn('user_id', $userIds)->delete();
        LessonProgress::query()->whereIn('enrollment_id', $enrollmentIds)->delete();
        CourseEnrollment::query()->where('course_id', $course->id)->whereIn('user_id', $userIds)->delete();
    }
}
