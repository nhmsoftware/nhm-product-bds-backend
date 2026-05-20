<?php

namespace App\Modules\Attendance\Events;

use App\Modules\Attendance\Models\Attendance;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AttendanceCheckedOut
{
    use Dispatchable, SerializesModels;

    /**
     * Khởi tạo Domain Event check-out thành công.
     *
     * @param Attendance $attendance Bản ghi chấm công vừa được cập nhật check-out
     */
    public function __construct(
        public readonly Attendance $attendance
    ) {
    }
}
