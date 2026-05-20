<?php

namespace App\Modules\Attendance\Events;

use App\Modules\Attendance\Models\Attendance;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AttendanceCheckedIn
{
    use Dispatchable, SerializesModels;

    /**
     * Khởi tạo Domain Event chấm công thành công.
     *
     * @param Attendance $attendance Bản ghi chấm công vừa tạo
     */
    public function __construct(
        public readonly Attendance $attendance
    ) {
    }
}
