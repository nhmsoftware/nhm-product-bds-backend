<?php

namespace App\Modules\CustomerMeeting\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;

class CheckInMeetCustomerRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'regex:/^(0)[3|5|7|8|9][0-9]{8}$/'],
            'project_id' => ['required', 'uuid', 'exists:projects,id'],
            'image' => ['required', 'file', 'image', 'max:10240'], // Max 10MB
            'latitude' => ['required', 'numeric', 'not_in:0'],
            'longitude' => ['required', 'numeric', 'not_in:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required' => 'Vui lòng nhập đầy đủ thông tin khách hàng.',
            'customer_phone.required' => 'Vui lòng nhập đầy đủ thông tin khách hàng.',
            'customer_phone.regex' => 'Số điện thoại không hợp lệ.',
            'project_id.required' => 'Vui lòng chọn dự án quan tâm.',
            'project_id.exists' => 'Dự án quan tâm không hợp lệ.',
            'image.required' => 'Vui lòng chụp ảnh thực tế.',
            'image.file' => 'Dung lượng ảnh quá lớn (vượt giới hạn server) hoặc lỗi mạng khi tải lên.',
            'image.image' => 'File tải lên phải là hình ảnh (jpg, png, ...).',
            'image.max' => 'Dung lượng ảnh không được vượt quá 10MB.',
            'image.uploaded' => 'Dung lượng ảnh quá lớn (vượt giới hạn server) hoặc lỗi mạng khi tải lên.',
            'latitude.required' => 'Không thể xác định vị trí hiện tại.',
            'latitude.not_in' => 'Không thể xác định vị trí hiện tại.',
            'longitude.required' => 'Không thể xác định vị trí hiện tại.',
            'longitude.not_in' => 'Không thể xác định vị trí hiện tại.',
        ];
    }
}
