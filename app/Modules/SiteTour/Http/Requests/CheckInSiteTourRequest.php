<?php

namespace App\Modules\SiteTour\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;

class CheckInSiteTourRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'uuid', 'exists:areas,id'],
            'unit_code' => ['required', 'string', 'max:100'],
            'customer_name' => ['required', 'string', 'max:255'],
            'image' => ['required', 'file', 'image', 'max:10240'], // Max 10MB
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'project_id.required' => 'Vui lòng chọn khu đất.',
            'project_id.exists' => 'Khu đất không hợp lệ.',
            'unit_code.required' => 'Vui lòng nhập mã lô/căn hộ.',
            'customer_name.required' => 'Vui lòng nhập tên khách hàng.',
            'image.required' => 'Vui lòng chụp ảnh tại khu đất.',
            'image.image' => 'Minh chứng phải là hình ảnh.',
            'latitude.required' => 'Vui lòng bật định vị GPS.',
            'longitude.required' => 'Vui lòng bật định vị GPS.',
        ];
    }
}
