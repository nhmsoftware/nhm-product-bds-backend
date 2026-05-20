<?php

namespace App\Modules\Leave\DTO;

use Illuminate\Http\Request;

/**
 * DTO đóng gói dữ liệu yêu cầu xin nghỉ phép mới của nhân viên.
 */
final class CreateLeaveDTO
{
    /**
     * Khởi tạo DTO.
     *
     * @param string $userId ID của nhân viên gửi yêu cầu
     * @param string $leaveType Loại nghỉ phép (annual, unpaid, personal, etc.)
     * @param string $startDate Ngày bắt đầu nghỉ phép (Y-m-d)
     * @param string $endDate Ngày kết thúc nghỉ phép (Y-m-d)
     * @param string $reason Lý do xin nghỉ phép
     */
    public function __construct(
        public readonly string $userId,
        public readonly string $leaveType,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly string $reason,
    ) {
    }

    /**
     * Khởi tạo DTO từ HTTP Request.
     *
     * @param Request $request
     * @param string $userId
     * @return self
     */
    public static function fromRequest(Request $request, string $userId): self
    {
        return new self(
            userId: $userId,
            leaveType: $request->input('leave_type'),
            startDate: $request->input('start_date'),
            endDate: $request->input('end_date'),
            reason: $request->input('reason'),
        );
    }

    /**
     * Chuyển đổi dữ liệu DTO thành mảng để tạo mới Model.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'leave_type' => $this->leaveType,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'reason' => $this->reason,
            'status' => 'pending', // Mặc định đơn mới gửi sẽ ở trạng thái chờ duyệt
        ];
    }
}
