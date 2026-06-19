<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Modules\Learning\Models\Course;
use App\Modules\Learning\Models\CourseEnrollment;
use App\Modules\Learning\Models\Enums\CourseEnrollmentStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Chặn truy cập API kho hàng nếu nhân viên chưa hoàn thành onboarding.
 *
 * Điều kiện pass: không có khóa học bắt buộc (is_required = true),
 * HOẶC có enrollment COMPLETED cho khóa học đó.
 */
class RequireOnboardingCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $requiredCourse = Course::where('is_required', true)->first();

        if ($requiredCourse === null) {
            return $next($request);
        }

        $completed = CourseEnrollment::where('user_id', $user->id)
            ->where('course_id', $requiredCourse->id)
            ->where('status', CourseEnrollmentStatus::COMPLETED)
            ->exists();

        if (!$completed) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn cần hoàn thành khóa học onboarding và được admin xác nhận trước khi truy cập kho hàng.',
            ], 403);
        }

        return $next($request);
    }
}
