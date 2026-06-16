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
use App\Modules\Learning\Models\Enums\LessonProgressStatus;
use Illuminate\Support\Str;

class LearningFakeDataSeeder extends Seeder
{
    private const VIDEO_URL = 'https://dswa1xdat8uez.cloudfront.net/27yvp%2Ffile%2F900a804230b16379a024523ea4672d84_dcc4514484c10342f3ce0ae9da0a529b.mp4?response-content-disposition=inline%3Bfilename%3D%22900a804230b16379a024523ea4672d84_dcc4514484c10342f3ce0ae9da0a529b.mp4%22%3B&response-content-type=video%2Fmp4&Expires=1781622438&Signature=DefG1B3fOLzAR7EnNKHyki3Hv2hgxmpxlxlFAystYBVJgb4hZXS69MZbZNpLf45LcXRtQHJRTS3~IzIxcqsmA-~0VJx6hkwJn2yiB1xnjosfq04TFKeCxRPlrzw23MKRNwIm~S~fLZcqE5GOwiNOJd4Rr5tRpA~CTTFPqZHnvcxGp8-Zx3HIiuFmxW6Ktcp~aDkfUcOmICeVixg6IV2Qzflj2ow8WMX~8SViPDgt-~sspxSCaMrIShfuwZ5QLFRiH2tWrmB1uW1arKr92fNrYnuBgpE08gpAEkSqNs0ri8C54h5BFk58vdR277WECzR6CJBe-N6u0SPoDcWbqMZxQQ__&Key-Pair-Id=APKAJT5WQLLEOADKLHBQ';

    public function run()
    {
        $users = User::query()
            ->whereIn('email', ['employee@test.com', 'employee2@test.com'])
            ->get();

        if ($users->isEmpty()) {
            $users = collect([
                User::create([
                    'id' => Str::uuid()->toString(),
                    'name' => 'Demo User',
                    'email' => 'demo' . rand(100, 999) . '@example.com',
                    'password' => bcrypt('password'),
                ])
            ]);
        }

        $this->clearExistingFakeCourses();

        for ($i = 1; $i <= 10; $i++) {
            // 1. Tạo 10 khóa học (Course)
            $course = Course::create([
                'title' => 'Khóa học mẫu số ' . $i,
                'description' => 'Đây là mô tả cho khóa học mẫu số ' . $i . ' dành cho team frontend test giao diện.',
                'thumbnail' => 'https://via.placeholder.com/600x400?text=Course+' . $i,
                'is_required' => false,
                'order' => $i,
                'is_active' => true,
                'has_certificate' => true,
            ]);

            // 2. Tạo 10 bài học (CourseLesson) - mỗi khóa học có 1 bài học
            $lesson = CourseLesson::create([
                'course_id' => $course->id,
                'title' => 'Bài 1: Giới thiệu khóa học ' . $i,
                'content' => '<p>Nội dung text của bài học số ' . $i . '</p>',
                'video_url' => self::VIDEO_URL,
                'duration_seconds' => 30,
                'order' => 1,
                'is_active' => true,
                'attachments' => [
                    ['type' => 'pdf', 'name' => 'Tài liệu hướng dẫn', 'url' => 'http://localhost:8000/docs/sample.pdf']
                ],
            ]);

            // 3. Tạo 10 Quiz (CourseQuiz) - mỗi bài học có 1 câu hỏi
            $quiz = CourseQuiz::create([
                'lesson_id' => $lesson->id,
                'question' => 'Câu hỏi trắc nghiệm số ' . $i . ' là gì?',
                'options' => ['Đáp án A', 'Đáp án B', 'Đáp án C', 'Đáp án D'],
                'correct_option' => rand(0, 3),
            ]);

            // 4. Tạo lượt đăng ký khóa học cho các nhân viên demo
            foreach ($users as $user) {
                $enrollment = CourseEnrollment::create([
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'status' => CourseEnrollmentStatus::IN_PROGRESS,
                    'progress_percent' => 0.00,
                ]);

                // 5. Tạo tiến độ bài học
                LessonProgress::create([
                    'enrollment_id' => $enrollment->id,
                    'lesson_id' => $lesson->id,
                    'is_completed' => false,
                    'current_watch_seconds' => 0,
                ]);
            }

            // 6. Tạo 10 Lượt làm bài Quiz (QuizAttempt)
            QuizAttempt::create([
                'user_id' => $user->id,
                'quiz_id' => $quiz->id,
                'selected_option' => rand(0, 3),
                'is_correct' => rand(0, 1) == 1,
                'is_draft' => false,
            ]);
        }

        echo "Đã tạo thành công 10 dữ liệu mẫu cho mỗi bảng trong Module Learning!\n";
    }

    private function clearExistingFakeCourses(): void
    {
        $courses = Course::withTrashed()
            ->where('title', 'like', 'Khóa học mẫu số %')
            ->get();

        foreach ($courses as $course) {
            $lessonIds = CourseLesson::withTrashed()
                ->where('course_id', $course->id)
                ->pluck('id');
            $enrollmentIds = CourseEnrollment::query()
                ->where('course_id', $course->id)
                ->pluck('id');

            LessonProgress::query()->whereIn('enrollment_id', $enrollmentIds)->delete();
            CourseEnrollment::query()->where('course_id', $course->id)->delete();
            CourseQuiz::withTrashed()->whereIn('lesson_id', $lessonIds)->forceDelete();
            CourseLesson::withTrashed()->where('course_id', $course->id)->forceDelete();
            $course->forceDelete();
        }
    }
}
