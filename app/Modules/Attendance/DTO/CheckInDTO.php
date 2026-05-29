<?php

namespace App\Modules\Attendance\DTO;

use Illuminate\Http\Request;

final class CheckInDTO
{
    /**
     * DTO đóng gói thông tin yêu cầu check-in của nhân viên.
     *
     * @param string $userId
     * @param string $method
     * @param string|null $latitude
     * @param string|null $longitude
     * @param string|null $wifiSsid
     * @param string|null $deviceName
     */
    public function __construct(
        public readonly string $userId,
        public readonly string $method,
        public readonly ?string $latitude,
        public readonly ?string $longitude,
        public readonly ?string $wifiSsid,
        public readonly ?string $deviceName,
    ) {
    }

    /**
     * Khởi tạo DTO từ HTTP Request.
     *
     * @param Request $request
     * @param string $userId
     * @return self
     */
    public static function fromRequest(Request $request, string $userId): self
    {
        return new self(
            userId: $userId,
            method: $request->input('method'),
            latitude: $request->input('latitude') ? (string) $request->input('latitude') : null,
            longitude: $request->input('longitude') ? (string) $request->input('longitude') : null,
            wifiSsid: $request->input('wifi_ssid'),
            deviceName: $request->input('device_name') ?? $request->header('User-Agent') ?? 'Unknown Device',
        );
    }
}
