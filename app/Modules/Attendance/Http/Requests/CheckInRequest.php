<?php

namespace App\Modules\Attendance\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;

final class CheckInRequest extends FormRequest
{
    use HandleApi;

    /**
     * Xác thực quyền của người dùng đối với request này.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Các quy tắc xác thực cho dữ liệu check-in.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'method' => ['required', 'string', 'in:gps,wifi'],
            'latitude' => ['required_if:method,gps', 'nullable', 'numeric'],
            'longitude' => ['required_if:method,gps', 'nullable', 'numeric'],
            'wifi_ssid' => ['required_if:method,wifi', 'nullable', 'string'],
            'device_name' => ['nullable', 'string'],
        ];
    }

    /**
     * Các thông điệp lỗi tùy chỉnh khi xác thực thất bại.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'method.required' => 'Vui lòng chọn phương thức check-in.',
            'method.in' => 'Phương thức check-in không hợp lệ.',
            'latitude.required_if' => 'Vui lòng cung cấp tọa độ vĩ độ (latitude) khi check-in bằng GPS.',
            'latitude.numeric' => 'Vĩ độ phải là một số hợp lệ.',
            'longitude.required_if' => 'Vui lòng cung cấp tọa độ kinh độ (longitude) khi check-in bằng GPS.',
            'longitude.numeric' => 'Kinh độ phải là một số hợp lệ.',
            'wifi_ssid.required_if' => 'Vui lòng cung cấp thông tin WiFi SSID khi check-in bằng WiFi.',
        ];
    }
}
