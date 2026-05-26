<?php

namespace App\Modules\Dashboard\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Core\Traits\HandleApi;

class DeleteCommentRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|string|in:lot_internal,news_public,news_internal',
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Loại bình luận là bắt buộc.',
            'type.in' => 'Loại bình luận không hợp lệ.',
        ];
    }
}
