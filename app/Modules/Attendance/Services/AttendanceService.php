<?php

namespace App\Modules\Attendance\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Attendance\DTO\CheckInDTO;
use App\Modules\Attendance\DTO\CheckOutDTO;
use App\Modules\Attendance\Events\AttendanceCheckedIn;
use App\Modules\Attendance\Events\AttendanceCheckedOut;
use App\Modules\Attendance\Interfaces\AttendanceRepositoryInterface;
use App\Modules\Attendance\Interfaces\AttendanceServiceInterface;
use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use Carbon\Carbon;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Modules\Attendance\Models\Enums\AttendanceStatus;

final class AttendanceService extends BaseService implements AttendanceServiceInterface
{
    /**
     * Khởi tạo Service với các repositories cần thiết.
     *
     * @param AttendanceRepositoryInterface $attendanceRepository
     * @param AuthRepositoryInterface $authRepository
     */
    public function __construct(
        private readonly AttendanceRepositoryInterface $attendanceRepository,
        private readonly AuthRepositoryInterface $authRepository
    ) {
    }

    /**
     * Thực hiện check-in cho nhân viên tại văn phòng qua GPS hoặc WiFi (UC-036).
     *
     * @param CheckInDTO $dto DTO chứa thông tin chi tiết về tọa độ hoặc Wifi SSID của thiết bị
     * @return ServiceReturn Trả về ServiceReturn chứa kết quả xử lý check-in
     * @throws \Throwable Ném ra exception nếu xảy ra lỗi trong quá trình thực thi database transaction
     */
    public function checkIn(CheckInDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // 1. Kiểm tra tài khoản người dùng và trạng thái hoạt động (Preconditions)
            $user = $this->authRepository->findById($dto->userId);
            $this->validate($user !== null, 'Người dùng không tồn tại.', 404);
            $this->validate($user->is_active, 'Tài khoản của bạn đã bị khóa hoặc đang ngưng hoạt động.', 403);
            
            // Employee check-in: Chỉ cho phép vai trò admin, agent, broker check-in
            $this->validate(
                in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::CEO, UserRole::DIRECTOR, UserRole::MANAGER, UserRole::EMPLOYEE], true),
                'Tài khoản không được cấp quyền sử dụng chức năng Check-in.',
                403
            );

            $today = Carbon::today()->toDateString();

            // 2. Kiểm tra xem nhân viên đã thực hiện check-in hôm nay chưa (A4 - Đã check-in trong ngày)
            $existingAttendance = $this->attendanceRepository->findByUserAndDate($dto->userId, $today);
            $this->validate($existingAttendance === null, 'Bạn đã check-in hôm nay.', 400);

            // 3. Tải các cấu hình về văn phòng từ database settings hoặc fallback về config
            $settings = \App\Modules\Area\Models\InventorySetting::whereIn('key', [
                'attendance_office_latitude',
                'attendance_office_longitude',
                'attendance_office_radius_meters',
                'attendance_office_wifi_ssid',
                'attendance_shift_start_time'
            ])->pluck('value', 'key');

            $config = config('attendance');
            $officeLat = (float) data_get($settings->get('attendance_office_latitude'), 'latitude', $config['office_latitude'] ?? 10.7769);
            $officeLng = (float) data_get($settings->get('attendance_office_longitude'), 'longitude', $config['office_longitude'] ?? 106.7009);
            $allowedRadius = (int) data_get($settings->get('attendance_office_radius_meters'), 'radius', $config['office_radius_meters'] ?? 100);
            $allowedWifiSsid = (string) data_get($settings->get('attendance_office_wifi_ssid'), 'wifi_ssid', $config['office_wifi_ssid'] ?? 'BDS_Office_Wifi');
            $shiftStartTimeStr = (string) data_get($settings->get('attendance_shift_start_time'), 'shift_start_time', $config['shift_start_time'] ?? '08:30:00');

            // 4. Kiểm tra điều kiện vùng địa lý (GPS) hoặc mạng kết nối (WiFi)
            if ($dto->method === 'gps') {
                // A6 - Lỗi lấy vị trí thiết bị
                $this->validate(
                    $dto->latitude !== null && $dto->longitude !== null && $dto->latitude != 0 && $dto->longitude != 0,
                    'Không thể xác định vị trí hiện tại. Vui lòng bật GPS trên thiết bị.',
                    400
                );
                
                // Tính khoảng cách từ thiết bị đến tọa độ văn phòng
                $distance = $this->calculateDistance((float) $dto->latitude, (float) $dto->longitude, $officeLat, $officeLng);
                
                // A3 - Ngoài phạm vi check-in cho phép
                $this->validate(
                    $distance <= $allowedRadius,
                    'Bạn không nằm trong khu vực check-in hợp lệ.',
                    400
                );
            } elseif ($dto->method === 'wifi') {
                // A2 - Wifi chưa bật hoặc chưa kết nối Wifi văn phòng
                $this->validate(
                    $dto->wifiSsid !== null && $dto->wifiSsid !== '',
                    'Vui lòng kết nối Wifi văn phòng để thực hiện check-in.',
                    400
                );
                
                // A3 - Ngoài phạm vi check-in cho phép (Wifi SSID không khớp Wifi văn phòng)
                $this->validate(
                    strcasecmp($dto->wifiSsid, $allowedWifiSsid) === 0,
                    'Bạn không nằm trong khu vực check-in hợp lệ. Vui lòng kết nối đúng Wifi văn phòng.',
                    400
                );
            }

            // 5. Xác định trạng thái chấm công (Đúng giờ hoặc Đi trễ)
            $now = Carbon::now();
            $shiftStartTime = Carbon::createFromTimeString($shiftStartTimeStr);

            $attendanceStatus = $now->greaterThan($shiftStartTime) ? AttendanceStatus::LATE : AttendanceStatus::PRESENT;
            $note = $attendanceStatus === AttendanceStatus::LATE
                ? 'Đi làm trễ (Giờ quy định: ' . $shiftStartTime->format('H:i') . ')'
                : 'Đi làm đúng giờ';

            // 6. Ghi nhận dữ liệu check-in
            $attendanceData = [
                'user_id' => $dto->userId,
                'work_date' => $today,
                'check_in_at' => $now,
                'check_in_lat' => $dto->method === 'gps' ? $dto->latitude : null,
                'check_in_lng' => $dto->method === 'gps' ? $dto->longitude : null,
                'check_in_method' => $dto->method,
                'check_in_wifi_ssid' => $dto->method === 'wifi' ? $dto->wifiSsid : null,
                'check_in_device_name' => $dto->deviceName,
                'status' => $attendanceStatus,
                'note' => $note,
            ];

            $attendance = $this->attendanceRepository->create($attendanceData);

            // 7. Bắn Domain Event báo hiệu check-in thành công
            event(new AttendanceCheckedIn($attendance));

            return $this->success(
                data: $attendance->toArray(),
                message: 'Check-in thành công.'
            );
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            // Bắt lỗi unique constraint (race condition hoặc bản ghi bị soft-delete)
            if ($e instanceof \Illuminate\Database\UniqueConstraintViolationException) {
                return \App\Core\Services\ServiceReturn::error(
                    message: 'Bạn đã check-in hôm nay.',
                    exception: $e,
                    code: 400,
                );
            }
            return null; // Các lỗi khác vẫn xử lý theo mặc định
        });
     }

    /**
     * Thực hiện check-out cho nhân viên tại văn phòng qua GPS hoặc WiFi (UC-037).
     *
     * @param CheckOutDTO $dto DTO chứa thông tin chi tiết về tọa độ hoặc Wifi SSID của thiết bị
     * @return ServiceReturn Trả về ServiceReturn chứa kết quả xử lý check-out
     * @throws \Throwable Ném ra exception nếu xảy ra lỗi trong quá trình thực thi database transaction
     */
    public function checkOut(CheckOutDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // 1. Kiểm tra tài khoản người dùng và trạng thái hoạt động (Preconditions)
            $user = $this->authRepository->findById($dto->userId);
            $this->validate($user !== null, 'Người dùng không tồn tại.', 404);
            $this->validate($user->is_active, 'Tài khoản của bạn đã bị khóa hoặc đang ngưng hoạt động.', 403);
            
            // Employee check-out: Chỉ cho phép vai trò admin, agent, broker check-out
            $this->validate(
                in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::CEO, UserRole::DIRECTOR, UserRole::MANAGER, UserRole::EMPLOYEE], true),
                'Tài khoản không được cấp quyền sử dụng chức năng Check-out.',
                403
            );

            $today = Carbon::today()->toDateString();

            // 2. Kiểm tra xem nhân viên đã thực hiện check-in hôm nay chưa (A1 - Employee chưa check-in trong ngày)
            $existingAttendance = $this->attendanceRepository->findByUserAndDate($dto->userId, $today);
            $this->validate($existingAttendance !== null, 'Bạn chưa check-in hôm nay.', 400);

            // 3. Kiểm tra xem nhân viên đã thực hiện check-out hôm nay chưa (A5 - Đã check-out trong ngày)
            $this->validate($existingAttendance->check_out_at === null, 'Bạn đã check-out hôm nay.', 400);

            // 4. Tải các cấu hình về văn phòng từ database settings hoặc fallback về config
            $settings = \App\Modules\Area\Models\InventorySetting::whereIn('key', [
                'attendance_office_latitude',
                'attendance_office_longitude',
                'attendance_office_radius_meters',
                'attendance_office_wifi_ssid',
            ])->pluck('value', 'key');

            $config = config('attendance');
            $officeLat = (float) data_get($settings->get('attendance_office_latitude'), 'latitude', $config['office_latitude'] ?? 10.7769);
            $officeLng = (float) data_get($settings->get('attendance_office_longitude'), 'longitude', $config['office_longitude'] ?? 106.7009);
            $allowedRadius = (int) data_get($settings->get('attendance_office_radius_meters'), 'radius', $config['office_radius_meters'] ?? 100);
            $allowedWifiSsid = (string) data_get($settings->get('attendance_office_wifi_ssid'), 'wifi_ssid', $config['office_wifi_ssid'] ?? 'BDS_Office_Wifi');

            // 5. Kiểm tra điều kiện vùng địa lý (GPS) hoặc mạng kết nối (WiFi)
            if ($dto->method === 'gps') {
                // A2/A7 - Lỗi lấy vị trí thiết bị hoặc GPS chưa bật
                $this->validate(
                    $dto->latitude !== null && $dto->longitude !== null && $dto->latitude != 0 && $dto->longitude != 0,
                    'Vui lòng bật GPS để thực hiện check-out.',
                    400
                );
                
                // Tính khoảng cách từ thiết bị đến tọa độ văn phòng
                $distance = $this->calculateDistance((float) $dto->latitude, (float) $dto->longitude, $officeLat, $officeLng);
                
                // A4 - Ngoài phạm vi check-out cho phép
                $this->validate(
                    $distance <= $allowedRadius,
                    'Bạn không nằm trong khu vực check-out hợp lệ.',
                    400
                );
            } elseif ($dto->method === 'wifi') {
                // A3 - Wifi chưa bật hoặc chưa kết nối Wifi văn phòng
                $this->validate(
                    $dto->wifiSsid !== null && $dto->wifiSsid !== '',
                    'Vui lòng kết nối Wifi văn phòng để thực hiện check-out.',
                    400
                );
                
                // A4 - Ngoài phạm vi check-out cho phép (Wifi SSID không khớp Wifi văn phòng)
                $this->validate(
                    strcasecmp($dto->wifiSsid, $allowedWifiSsid) === 0,
                    'Bạn không nằm trong khu vực check-out hợp lệ. Vui lòng kết nối đúng Wifi văn phòng.',
                    400
                );
            }

            // 6. Ghi nhận dữ liệu check-out & Tính tổng thời gian làm việc
            $now = Carbon::now();
            $checkInAt = Carbon::parse($existingAttendance->check_in_at);
            // Tính chênh lệch giây tuyệt đối, đảm bảo không bao giờ âm
            $durationInSeconds = max(0, (int) abs($now->diffInSeconds($checkInAt)));
            
            $hours = intdiv($durationInSeconds, 3600);
            $minutes = intdiv($durationInSeconds % 3600, 60);
            $durationText = "{$hours} giờ {$minutes} phút";

            // Đọc cấu hình công ty khi làm việc dưới 6 tiếng
            $under6Setting = \App\Modules\Area\Models\InventorySetting::where('key', 'attendance_under_6_hours_work_day')->first();
            $under6WorkDay = floatval(data_get($under6Setting?->value, 'work_day', 0.5));

            $status = $existingAttendance->status;
            $noteSuffix = '';
            if ($durationInSeconds < 21600) { // Dưới 6 tiếng (6 * 3600 = 21600 giây)
                if ($under6WorkDay == 0.5) {
                    $status = AttendanceStatus::HALF_DAY;
                    $noteSuffix = ' (Thời gian làm việc dưới 6 tiếng, tính 0.5 công)';
                } elseif ($under6WorkDay == 0.0) {
                    $status = AttendanceStatus::ABSENT;
                    $noteSuffix = ' (Thời gian làm việc dưới 6 tiếng, tính 0.0 công)';
                } else {
                    $noteSuffix = ' (Thời gian làm việc dưới 6 tiếng, tính 1.0 công)';
                }
            } else {
                $noteSuffix = ' (Thời gian làm việc từ 6 tiếng trở lên, tính 1.0 công)';
            }

            $updateData = [
                'check_out_at' => $now,
                'check_out_lat' => $dto->method === 'gps' ? $dto->latitude : null,
                'check_out_lng' => $dto->method === 'gps' ? $dto->longitude : null,
                'check_out_method' => $dto->method,
                'check_out_wifi_ssid' => $dto->method === 'wifi' ? $dto->wifiSsid : null,
                'check_out_device_name' => $dto->deviceName,
                'status' => $status,
                'note' => ($existingAttendance->note ? $existingAttendance->note . ' | ' : '') . 'Check-out thành công. Tổng thời gian làm việc: ' . $durationText . $noteSuffix,
            ];

            // Cập nhật record chấm công
            $existingAttendance->update($updateData);

            // Tải lại model để có dữ liệu mới nhất
            $updatedAttendance = $existingAttendance->fresh();

            // 7. Bắn Domain Event báo hiệu check-out thành công
            event(new AttendanceCheckedOut($updatedAttendance));

            return $this->success(
                data: [
                    'attendance' => $updatedAttendance->toArray(),
                    'working_duration' => $durationText,
                    'working_seconds' => $durationInSeconds,
                ],
                message: 'Check-out thành công.'
            );
        }, useTransaction: true);
    }

    /**
     * Lấy trạng thái chấm công trong ngày hôm nay của nhân viên.
     *
     * @param string $userId ID của nhân viên cần kiểm tra (UUID)
     * @return ServiceReturn Trả về ServiceReturn chứa trạng thái chấm công của nhân viên hôm nay
     * @throws \Throwable
     */
    public function getTodayStatus(string $userId): ServiceReturn
    {
        return $this->execute(function () use ($userId) {
            $user = $this->authRepository->findById($userId);
            $this->validate($user !== null, 'Người dùng không tồn tại.', 404);

            $today = Carbon::today()->toDateString();
            $attendance = $this->attendanceRepository->findByUserAndDate($userId, $today);

            $settings = \App\Modules\Area\Models\InventorySetting::whereIn('key', [
                'attendance_office_latitude',
                'attendance_office_longitude',
                'attendance_office_radius_meters',
                'attendance_office_wifi_ssid',
                'attendance_shift_start_time'
            ])->pluck('value', 'key');

            $config = config('attendance');
            $officeLat = (float) data_get($settings->get('attendance_office_latitude'), 'latitude', $config['office_latitude'] ?? 10.7769);
            $officeLng = (float) data_get($settings->get('attendance_office_longitude'), 'longitude', $config['office_longitude'] ?? 106.7009);
            $allowedRadius = (int) data_get($settings->get('attendance_office_radius_meters'), 'radius', $config['office_radius_meters'] ?? 100);
            $allowedWifiSsid = (string) data_get($settings->get('attendance_office_wifi_ssid'), 'wifi_ssid', $config['office_wifi_ssid'] ?? 'BDS_Office_Wifi');
            $shiftStartTimeStr = (string) data_get($settings->get('attendance_shift_start_time'), 'shift_start_time', $config['shift_start_time'] ?? '08:30:00');

            $data = [
                'has_checked_in' => $attendance !== null,
                'attendance' => $attendance ? $attendance->toArray() : null,
                'office_config' => [
                    'office_latitude' => $officeLat,
                    'office_longitude' => $officeLng,
                    'office_radius_meters' => $allowedRadius,
                    'office_wifi_ssid' => $allowedWifiSsid,
                    'shift_start_time' => $shiftStartTimeStr,
                ]
            ];

            return $this->success(
                data: $data,
                message: 'Lấy trạng thái chấm công hôm nay thành công.'
            );
        }, useTransaction: false);
    }

    /**
     * Tính khoảng cách giữa 2 cặp tọa độ bằng công thức Haversine (trả về mét).
     *
     * @param float $lat1 Vĩ độ điểm 1
     * @param float $lng1 Kinh độ điểm 1
     * @param float $lat2 Vĩ độ điểm 2
     * @param float $lng2 Kinh độ điểm 2
     * @return float Khoảng cách theo mét
     */
    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // Bán kính Trái Đất theo mét
        
        $latFrom = deg2rad($lat1);
        $lngFrom = deg2rad($lng1);
        $latTo = deg2rad($lat2);
        $lngTo = deg2rad($lng2);

        $latDelta = $latTo - $latFrom;
        $lngDelta = $lngTo - $lngFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lngDelta / 2), 2)));
            
        return $angle * $earthRadius;
    }
}
