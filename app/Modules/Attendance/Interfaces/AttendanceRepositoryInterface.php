<?php

namespace App\Modules\Attendance\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;

interface AttendanceRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Tìm bản ghi chấm công của nhân viên theo ngày làm việc.
     *
     * @param string $userId ID của nhân viên (UUID)
     * @param string $date Ngày làm việc cần tìm (Y-m-d)
     * @return \App\Modules\Attendance\Models\Attendance|null Trả về model chấm công hoặc null nếu chưa chấm công
     */
    public function findByUserAndDate(string $userId, string $date);

    /**
     * Đếm số ngày đi làm của danh sách userIds trong khoảng thời gian.
     */
    public function countWorkDays(array|string $userIds, ?string $fromDate, ?string $toDate): float;

    public function countWorkDaysByUsers(array $userIds, ?string $fromDate, ?string $toDate): \Illuminate\Support\Collection;

    /**
     * Đếm số ngày nghỉ cố định của danh sách userIds trong khoảng thời gian.
     */
    public function countFixedScheduleAbsences(array|string $userIds, ?string $fromDate, ?string $toDate): int;

    public function countFixedScheduleAbsencesByUsers(array $userIds, ?string $fromDate, ?string $toDate): \Illuminate\Support\Collection;
}
