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
     * Đếm số ngày làm việc của nhân viên trong khoảng thời gian.
     *
     * @param array|string $userIds ID của nhân viên (UUID hoặc mảng ID)
     * @param string|null $fromDate Ngày bắt đầu (Y-m-d)
     * @param string|null $toDate Ngày kết thúc (Y-m-d)
     * @return int Số ngày làm việc trong khoảng thời gian
     */
    public function countWorkDays(array|string $userIds, ?string $fromDate, ?string $toDate): int
    {
        $userIdsArray = is_array($userIds) ? $userIds : [$userIds];
        $query = $this->model->whereIn('user_id', $userIdsArray)
            ->whereIn('status', [1, 2, 4]); // WORK, LATE, OVERTIME

        if ($fromDate) {
            $query->whereDate('work_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('work_date', '<=', $toDate);
        }

        return $query->count();
    }

    public function countWorkDaysByUsers(array $userIds, ?string $fromDate, ?string $toDate): \Illuminate\Support\Collection
    {
        $query = $this->model->whereIn('user_id', $userIds)
            ->whereIn('status', [1, 2, 4]);

        if ($fromDate) {
            $query->whereDate('work_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('work_date', '<=', $toDate);
        }

        return $query->selectRaw('user_id, count(*) as count')
            ->groupBy('user_id')
            ->pluck('count', 'user_id');
    }

    /**
     * Đếm số ngày nghỉ theo lịch của nhân viên trong khoảng thời gian.
     *
     * @param array|string $userIds ID của nhân viên (UUID hoặc mảng ID)
     * @param string|null $fromDate Ngày bắt đầu (Y-m-d)
     * @param string|null $toDate Ngày kết thúc (Y-m-d)
     * @return int Số ngày nghỉ theo lịch trong khoảng thời gian
     */
    public function countFixedScheduleAbsences(array|string $userIds, ?string $fromDate, ?string $toDate): int
    {
        $userIdsArray = is_array($userIds) ? $userIds : [$userIds];
        $query = $this->model->whereIn('user_id', $userIdsArray)
            ->where('status', 3); // ABSENT

        if ($fromDate) {
            $query->whereDate('work_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('work_date', '<=', $toDate);
        }

        return $query->count();
    }

    public function countFixedScheduleAbsencesByUsers(array $userIds, ?string $fromDate, ?string $toDate): \Illuminate\Support\Collection
    {
        $query = $this->model->whereIn('user_id', $userIds)
            ->where('status', 3);

        if ($fromDate) {
            $query->whereDate('work_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('work_date', '<=', $toDate);
        }

        return $query->selectRaw('user_id, count(*) as count')
            ->groupBy('user_id')
            ->pluck('count', 'user_id');
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
