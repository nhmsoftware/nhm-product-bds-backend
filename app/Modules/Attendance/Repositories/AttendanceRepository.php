<?php

namespace App\Modules\Attendance\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Attendance\Interfaces\AttendanceRepositoryInterface;
use App\Modules\Attendance\Models\Attendance;

final class AttendanceRepository extends BaseRepository implements AttendanceRepositoryInterface
{
    /**
     * Xác định class Model đại diện cho Repository này.
     *
     * @return string Tên lớp Model dạng chuỗi
     */
    public function getModel(): string
    {
        return Attendance::class;
    }

    /**
     * Tìm bản ghi chấm công của nhân viên theo ngày làm việc.
     *
     * @param string $userId ID của nhân viên (UUID)
     * @param string $date Ngày làm việc cần tìm (Y-m-d)
     * @return Attendance|null Trả về model chấm công hoặc null nếu chưa chấm công
     */
    public function findByUserAndDate(string $userId, string $date)
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('work_date', $date)
            ->first();
    }
}
