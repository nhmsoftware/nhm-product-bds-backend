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
    public function run()
    {
        // Lấy user đầu tiên trong DB để gán dữ liệu. Nếu chưa có user nào, tạo 1 user ảo.
        $user = User::first();
        if (!$user) {
            $user = User::create([
                'id' => Str::uuid()->toString(),
                'name' => 'Demo User',
                'email' => 'demo' . rand(100, 999) . '@example.com',
                'password' => bcrypt('password'),
            ]);
        }

        for ($i = 1; $i <= 10; $i++) {
            // 1. Tạo 10 khóa học (Course)
            $course = Course::create([
                'title' => 'Khóa học mẫu số ' . $i,
                'description' => 'Đây là mô tả cho khóa học mẫu số ' . $i . ' dành cho team frontend test giao diện.',
                'thumbnail' => 'https://via.placeholder.com/600x400?text=Course+' . $i,
                'is_required' => rand(0, 1) == 1,
                'order' => $i,
                'is_active' => true,
                'has_certificate' => true,
            ]);

            // 2. Tạo 10 bài học (CourseLesson) - mỗi khóa học có 1 bài học
            $lesson = CourseLesson::create([
                'course_id' => $course->id,
                'title' => 'Bài 1: Giới thiệu khóa học ' . $i,
                'content' => '<p>Nội dung text của bài học số ' . $i . '</p>',
                'video_url' => 'http://localhost:8000/videos/demo.mp4',
                'duration_seconds' => 15,
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

            // 4. Tạo 10 Lượt đăng ký khóa học (CourseEnrollment) cho User
            $enrollment = CourseEnrollment::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'status' => CourseEnrollmentStatus::IN_PROGRESS,
                'progress_percent' => 50.00,
            ]);

            // 5. Tạo 10 Tiến độ bài học (LessonProgress)
            $progress = LessonProgress::create([
                'enrollment_id' => $enrollment->id,
                'lesson_id' => $lesson->id,
                'is_completed' => false,
                'current_watch_seconds' => 120,
            ]);

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
}
