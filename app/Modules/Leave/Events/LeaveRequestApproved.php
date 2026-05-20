<?php

namespace App\Modules\Leave\Events;

use App\Modules\Leave\Models\LeaveRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Sự kiện được kích hoạt khi Team Leader duyệt đơn xin nghỉ phép thành công.
 */
final class LeaveRequestApproved
{
    use Dispatchable, SerializesModels;

    /**
     * Khởi tạo sự kiện với thông tin đơn nghỉ phép đã được duyệt.
     *
     * @param LeaveRequest $leaveRequest
     */
    public function __construct(
        public readonly LeaveRequest $leaveRequest
    ) {
    }
}
