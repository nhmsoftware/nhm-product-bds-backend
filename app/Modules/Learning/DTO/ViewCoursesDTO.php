<?php

namespace App\Modules\Learning\DTO;

use Illuminate\Http\Request;

/**
 * DTO đóng gói thông tin định danh và cơ cấu tổ chức của nhân viên để tải danh sách khóa học bắt buộc.
 */
final class ViewCoursesDTO
{
    /**
     * Khởi tạo DTO.
     *
     * @param string $userId ID của nhân viên
     * @param string|null $department Phòng ban của nhân viên
     * @param string|null $jobPosition Vị trí công việc của nhân viên
     */
    public function __construct(
        public readonly string $userId,
        public readonly ?string $department,
        public readonly ?string $jobPosition,
    ) {
    }

    /**
     * Khởi tạo DTO từ HTTP Request và thông tin định danh User.
     *
     * @param Request $request
     * @param string $userId
     * @return self
     */
    public static function fromRequest(Request $request, string $userId): self
    {
        $user = $request->user();
        return new self(
            userId: $userId,
            department: $user?->department,
            jobPosition: $user?->job_position,
        );
    }
}
