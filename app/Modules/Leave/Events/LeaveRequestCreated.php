<?php

namespace App\Modules\Leave\Events;

use App\Modules\Leave\Models\LeaveRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Sự kiện được kích hoạt ngay khi nhân viên gửi thành công yêu cầu xin nghỉ phép vào hệ thống.
 */
final class LeaveRequestCreated
{
    use Dispatchable, SerializesModels;

    /**
     * Khởi tạo sự kiện với thông tin đơn xin nghỉ phép tương ứng.
     *
     * @param LeaveRequest $leaveRequest
     */
    public function __construct(
        public readonly LeaveRequest $leaveRequest
    ) {
    }
}
