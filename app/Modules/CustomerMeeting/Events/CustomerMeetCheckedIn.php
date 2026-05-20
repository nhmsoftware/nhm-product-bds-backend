<?php

namespace App\Modules\CustomerMeeting\Events;

use App\Modules\CustomerMeeting\Models\CustomerMeeting;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Sự kiện nhân viên check-in gặp khách hàng tại dự án thành công.
 */
final class CustomerMeetCheckedIn
{
    use Dispatchable, SerializesModels;

    /**
     * Khởi tạo sự kiện.
     *
     * @param CustomerMeeting $meeting Hoạt động gặp khách hàng được lưu
     */
    public function __construct(
        public readonly CustomerMeeting $meeting
    ) {
    }
}
