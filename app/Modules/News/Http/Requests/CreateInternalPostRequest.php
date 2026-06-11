<?php

namespace App\Modules\News\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Core\Traits\HandleApi;

class CreateInternalPostRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => 'required|string|min:1',
            'title' => 'nullable|string|max:255',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'thumbnail_url' => 'nullable|string',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|mimes:pdf,doc,docx,jpeg,jpg,png|max:10240',
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'Vui lòng nhập nội dung bài viết.',
            'thumbnail.image' => 'File hình ảnh không hợp lệ.',
            'thumbnail.mimes' => 'File hình ảnh không hợp lệ.',
            'thumbnail.max' => 'File hình ảnh không hợp lệ.',
            'attachments.*.mimes' => 'Tài liệu đính kèm không hợp lệ.',
            'attachments.*.max' => 'Dung lượng tài liệu không được vượt quá 10MB.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $errors = $validator->errors()->toArray();
        $firstError = count($errors) > 0 ? reset($errors)[0] : 'Dữ liệu không hợp lệ.';
        throw new \Illuminate\Http\Exceptions\HttpResponseException(
            $this->sendValidation($firstError, $errors)
        );
    }
}
