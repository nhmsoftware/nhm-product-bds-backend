<?php

namespace App\Modules\Leave\Events;

use App\Modules\Leave\Models\LeaveRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Sự kiện được kích hoạt khi Team Leader từ chối đơn xin nghỉ phép của nhân viên.
 */
final class LeaveRequestRejected
{
    use Dispatchable, SerializesModels;

    /**
     * Khởi tạo sự kiện với thông tin đơn nghỉ phép bị từ chối.
     *
     * @param LeaveRequest $leaveRequest
     */
    public function __construct(
        public readonly LeaveRequest $leaveRequest
    ) {
    }
}
