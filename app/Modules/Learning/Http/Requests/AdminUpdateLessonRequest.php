<?php

namespace App\Modules\Learning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Core\Traits\HandleApi;

class AdminUpdateLessonRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'video_url' => 'nullable|string',
            'duration_seconds' => 'nullable|integer|min:0',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'attachments' => 'nullable|array',
            'attachments.*.type' => 'required_with:attachments|string|max:50',
            'attachments.*.url' => 'required_with:attachments|string|max:500',
            'attachments.*.name' => 'required_with:attachments|string|max:255',
        ];
    }
}
