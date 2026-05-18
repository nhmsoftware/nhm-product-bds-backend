<?php

namespace App\Modules\LegalVideo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Core\Traits\HandleApi;

class GetLegalVideoListRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'category' => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ];

        // Nếu người dùng thực hiện tìm kiếm (có tham số search)
        if ($this->has('search')) {
            $rules['search'] = 'required|string|max:255';
        } else {
            $rules['search'] = 'nullable|string|max:255';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'category.string' => 'Danh mục phải là một chuỗi ký tự.',
            'search.required' => 'Vui lòng nhập từ khóa tìm kiếm.',
            'search.string' => 'Từ khóa tìm kiếm phải là một chuỗi ký tự.',
            'search.max' => 'Từ khóa tìm kiếm không được vượt quá 255 ký tự.',
            'per_page.integer' => 'Số lượng mỗi trang phải là số nguyên.',
            'page.integer' => 'Trang hiện tại phải là số nguyên.',
        ];
    }
}
