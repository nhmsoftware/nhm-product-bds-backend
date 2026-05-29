<?php

declare(strict_types=1);

namespace App\Modules\News\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class CreateNewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'required|string|max:100',
            'type' => 'required|in:public,internal',
            'scope' => 'required_if:type,internal|in:company,department',
            'department' => 'required_if:scope,department|nullable|string',
            'status' => 'required|in:published,hidden',
            'thumbnail' => 'nullable|string',
            'summary' => 'nullable|string',
            'is_featured' => 'nullable|boolean'
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Vui lòng nhập đầy đủ thông tin bài viết.',
            'content.required' => 'Vui lòng nhập đầy đủ thông tin bài viết.',
            'category.required' => 'Vui lòng nhập đầy đủ thông tin bài viết.',
            'type.required' => 'Vui lòng nhập đầy đủ thông tin bài viết.',
            'scope.required_if' => 'Vui lòng nhập đầy đủ thông tin bài viết.',
            'department.required_if' => 'Vui lòng nhập đầy đủ thông tin bài viết.',
            'status.required' => 'Vui lòng nhập đầy đủ thông tin bài viết.',

            'title.string' => 'Nội dung bài viết không hợp lệ.',
            'title.max' => 'Nội dung bài viết không hợp lệ.',
            'content.string' => 'Nội dung bài viết không hợp lệ.',
            'category.string' => 'Nội dung bài viết không hợp lệ.',
            'category.max' => 'Nội dung bài viết không hợp lệ.',
            'type.in' => 'Nội dung bài viết không hợp lệ.',
            'scope.in' => 'Nội dung bài viết không hợp lệ.',
            'department.string' => 'Nội dung bài viết không hợp lệ.',
            'status.in' => 'Nội dung bài viết không hợp lệ.',
            'thumbnail.string' => 'Nội dung bài viết không hợp lệ.',
            'summary.string' => 'Nội dung bài viết không hợp lệ.',
            'is_featured.boolean' => 'Nội dung bài viết không hợp lệ.',
        ];
    }
}
