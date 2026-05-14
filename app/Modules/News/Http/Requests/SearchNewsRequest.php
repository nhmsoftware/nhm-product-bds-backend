<?php

namespace App\Modules\News\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Core\Traits\HandleApi;

class SearchNewsRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keyword' => 'required|string|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'keyword.required' => 'Vui lòng nhập từ khóa tìm kiếm.',
            'keyword.min' => 'Vui lòng nhập từ khóa tìm kiếm.',
        ];
    }

    protected function prepareForValidation()
    {
        if (!$this->has('keyword') && $this->has('search')) {
            $this->merge(['keyword' => $this->get('search')]);
        }
    }
}
