<?php

namespace App\Modules\Attendance\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Attendance\DTO\CheckInDTO;
use App\Modules\Attendance\DTO\CheckOutDTO;
use App\Modules\Attendance\Http\Requests\CheckInRequest;
use App\Modules\Attendance\Http\Requests\CheckOutRequest;
use App\Modules\Attendance\Interfaces\AttendanceServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class AttendanceController extends BaseController
{
    /**
     * Khởi tạo Controller với Service tương ứng.
     *
     * @param AttendanceServiceInterface $attendanceService
     */
    public function __construct(
        private readonly AttendanceServiceInterface $attendanceService
    ) {
    }

    #[OA\Post(
        path: '/api/v1/attendance/check-in',
        summary: 'Nhân viên thực hiện check-in (UC-036)',
        description: 'Cho phép nhân viên check-in vào ca bằng tọa độ GPS hoặc kết nối WiFi văn phòng.',
        tags: ['Attendance'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['method'],
                properties: [
                    new OA\Property(property: 'method', type: 'string', enum: ['gps', 'wifi'], example: 'gps', description: 'Phương thức check-in: gps hoặc wifi'),
                    new OA\Property(property: 'latitude', type: 'number', format: 'float', example: 10.7769, description: 'Vĩ độ GPS (Bắt buộc nếu method là gps)'),
                    new OA\Property(property: 'longitude', type: 'number', format: 'float', example: 106.7009, description: 'Kinh độ GPS (Bắt buộc nếu method là gps)'),
                    new OA\Property(property: 'wifi_ssid', type: 'string', example: 'BDS_Office_Wifi', description: 'Tên Wifi đang kết nối (Bắt buộc nếu method là wifi)'),
                    new OA\Property(property: 'device_name', type: 'string', example: 'iPhone 15 Pro', description: 'Tên thiết bị thực hiện check-in (Không bắt buộc)'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Check-in thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Check-in thành công.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Attendance'),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Yêu cầu không hợp lệ (ví dụ: đã check-in, ngoài vùng phủ sóng...)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Bạn không nằm trong khu vực check-in hợp lệ.'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Lỗi xác thực dữ liệu',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Dữ liệu không hợp lệ.'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'method', type: 'array', items: new OA\Items(type: 'string', example: 'Vui lòng chọn phương thức check-in.'))
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập (Unauthenticated)'),
            new OA\Response(response: 403, description: 'Không có quyền truy cập'),
        ]
    )]
    public function checkIn(CheckInRequest $request): JsonResponse
    {
        $dto = CheckInDTO::fromRequest($request, $request->user()->id);
        $result = $this->attendanceService->checkIn($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/attendance/check-out',
        description: 'Cho phép nhân viên check-out kết thúc ca làm việc tại văn phòng bằng tọa độ GPS hoặc kết nối WiFi văn phòng.',
        summary: 'Nhân viên thực hiện check-out (UC-037)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['method'],
                properties: [
                    new OA\Property(property: 'method', type: 'string', enum: ['gps', 'wifi'], example: 'gps', description: 'Phương thức check-out: gps hoặc wifi'),
                    new OA\Property(property: 'latitude', type: 'number', format: 'float', example: 10.7769, description: 'Vĩ độ GPS (Bắt buộc nếu method là gps)'),
                    new OA\Property(property: 'longitude', type: 'number', format: 'float', example: 106.7009, description: 'Kinh độ GPS (Bắt buộc nếu method là gps)'),
                    new OA\Property(property: 'wifi_ssid', type: 'string', example: 'BDS_Office_Wifi', description: 'Tên Wifi đang kết nối (Bắt buộc nếu method là wifi)'),
                    new OA\Property(property: 'device_name', type: 'string', example: 'iPhone 15 Pro', description: 'Tên thiết bị thực hiện check-out (Không bắt buộc)'),
                ]
            )
        ),
        security: [['sanctum' => []]],
        tags: ['Attendance'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Check-out thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Check-out thành công.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'attendance', ref: '#/components/schemas/Attendance'),
                                new OA\Property(property: 'working_duration', type: 'string', example: '8 giờ 15 phút', description: 'Tổng thời gian làm việc đã được tính toán'),
                                new OA\Property(property: 'working_seconds', type: 'integer', example: 29700, description: 'Tổng thời gian làm việc tính bằng giây'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Yêu cầu không hợp lệ (ví dụ: chưa check-in, đã check-out, ngoài vùng phủ sóng...)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Bạn chưa check-in hôm nay.'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Lỗi xác thực dữ liệu',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Dữ liệu không hợp lệ.'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'method', type: 'array', items: new OA\Items(type: 'string', example: 'Vui lòng chọn phương thức check-out.'))
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập (Unauthenticated)'),
            new OA\Response(response: 403, description: 'Không có quyền truy cập'),
        ]
    )]
    public function checkOut(CheckOutRequest $request): JsonResponse
    {
        $dto = CheckOutDTO::fromRequest($request, $request->user()->id);
        $result = $this->attendanceService->checkOut($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/attendance/today',
        summary: 'Lấy trạng thái check-in hôm nay (UC-036)',
        description: 'Kiểm tra trạng thái chấm công của nhân viên trong ngày hôm nay.',
        tags: ['Attendance'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lấy trạng thái thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Lấy trạng thái chấm công hôm nay thành công.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'has_checked_in', type: 'boolean', example: false),
                                new OA\Property(property: 'attendance', ref: '#/components/schemas/Attendance', nullable: true),
                                new OA\Property(
                                    property: 'office_config',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'office_latitude', type: 'number', format: 'float', example: 10.7769),
                                        new OA\Property(property: 'office_longitude', type: 'number', format: 'float', example: 106.7009),
                                        new OA\Property(property: 'office_radius_meters', type: 'integer', example: 100),
                                        new OA\Property(property: 'office_wifi_ssid', type: 'string', example: 'BDS_Office_Wifi'),
                                        new OA\Property(property: 'shift_start_time', type: 'string', example: '08:30:00'),
                                    ]
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập (Unauthenticated)'),
        ]
    )]
    public function todayStatus(Request $request): JsonResponse
    {
        $result = $this->attendanceService->getTodayStatus($request->user()->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
