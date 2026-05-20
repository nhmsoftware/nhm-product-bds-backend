<?php

namespace App\Modules\ActivityEvidence\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;

class UploadEvidenceRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,gif', 'max:10240'], // Giới hạn 10MB
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'Vui lòng chọn ảnh minh chứng.',
            'image.image' => 'Định dạng file không hợp lệ.',
            'image.mimes' => 'Định dạng file không hợp lệ.',
            'image.max' => 'Dung lượng file vượt quá giới hạn.',
        ];
    }
}
