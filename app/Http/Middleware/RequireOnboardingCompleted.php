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
 *
 * Lưu ý: Cột allowed_roles trong courses chuyển từ lưu integer (role value)
 * sang lưu string (role name). VD: [4] → ["ceo"], [1] → ["employee"]
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

        // CTV không cần onboarding - có thể xem kho hàng luôn
        if ($user->role?->name === 'ctv') {
            return $next($request);
        }

        $roleName = $user->role?->name;

        $requiredCourseIds = Course::query()
            ->where('is_active', true)
            ->where('is_required', true)
            ->where(function ($query) use ($roleName) {
                $query->whereNull('allowed_roles')
                    ->orWhereJsonLength('allowed_roles', 0);

                if ($roleName !== null) {
                    // Hỗ trợ cả format cũ (integer) và mới (string role name)
                    $query->orWhereJsonContains('allowed_roles', $roleName);
                }
            })
            ->pluck('id');

        if ($requiredCourseIds->isEmpty()) {
            return $next($request);
        }

        $completedCount = CourseEnrollment::where('user_id', $user->id)
            ->whereIn('course_id', $requiredCourseIds)
            ->where('status', CourseEnrollmentStatus::COMPLETED)
            ->distinct('course_id')
            ->count('course_id');

        if ($completedCount < $requiredCourseIds->count()) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn cần hoàn thành khóa học onboarding và được admin xác nhận trước khi truy cập kho hàng.',
            ], 403);
        }

        return $next($request);
    }
}
