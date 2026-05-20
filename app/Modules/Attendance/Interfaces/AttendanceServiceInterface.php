<?php

namespace App\Modules\Attendance\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Attendance\DTO\CheckInDTO;
use App\Modules\Attendance\DTO\CheckOutDTO;

interface AttendanceServiceInterface
{
    /**
     * Thực hiện check-in cho nhân viên tại văn phòng qua GPS hoặc WiFi (UC-036).
     *
     * @param CheckInDTO $dto DTO chứa thông tin chi tiết về tọa độ hoặc Wifi SSID của thiết bị
     * @return ServiceReturn Trả về ServiceReturn chứa kết quả xử lý check-in
     * @throws \Throwable Ném ra exception nếu xảy ra lỗi trong quá trình thực thi database transaction
     */
    public function checkIn(CheckInDTO $dto): ServiceReturn;

    /**
     * Thực hiện check-out cho nhân viên tại văn phòng qua GPS hoặc WiFi (UC-037).
     *
     * @param CheckOutDTO $dto DTO chứa thông tin chi tiết về tọa độ hoặc Wifi SSID của thiết bị
     * @return ServiceReturn Trả về ServiceReturn chứa kết quả xử lý check-out
     * @throws \Throwable Ném ra exception nếu xảy ra lỗi trong quá trình thực thi database transaction
     */
    public function checkOut(CheckOutDTO $dto): ServiceReturn;

    /**
     * Lấy trạng thái chấm công trong ngày hôm nay của nhân viên.
     *
     * @param string $userId ID của nhân viên cần kiểm tra (UUID)
     * @return ServiceReturn Trả về ServiceReturn chứa trạng thái chấm công của nhân viên hôm nay
     * @throws \Throwable
     */
    public function getTodayStatus(string $userId): ServiceReturn;
}
