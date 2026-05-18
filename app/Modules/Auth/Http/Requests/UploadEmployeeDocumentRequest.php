<?php

namespace App\Modules\Auth\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;

final class UploadEmployeeDocumentRequest extends FormRequest
{
    use HandleApi;
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => [
                'required',
                'string',
                'in:Hợp đồng lao động,Bằng cấp,Chứng chỉ,CCCD/CMND,Tài liệu khác',
            ],
            'file' => [
                'required',
                'file',
                'mimes:pdf,doc,docx,jpg,jpeg,png',
                'max:10240', // Tối đa 10MB
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Vui lòng chọn loại tài liệu.',
            'type.in'       => 'Loại tài liệu không hợp lệ.',
            
            'file.required' => 'Vui lòng chọn file cần tải lên.',
            'file.file'     => 'File không hợp lệ.',
            'file.mimes'    => 'Định dạng file không hợp lệ.', // A3
            'file.max'      => 'Dung lượng file vượt quá giới hạn cho phép.', // A4
        ];
    }
}
