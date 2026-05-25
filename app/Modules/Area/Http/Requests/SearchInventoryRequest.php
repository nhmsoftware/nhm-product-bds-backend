<?php

declare(strict_types=1);

namespace App\Modules\Area\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Core\Traits\HandleApi;

class SearchInventoryRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'keyword' => 'required|string|min:1',
        ];
    }

    /**
     * Get validation error messages.
     */
    public function messages(): array
    {
        return [
            'keyword.required' => 'Vui lòng nhập từ khóa tìm kiếm.',
            'keyword.min' => 'Vui lòng nhập từ khóa tìm kiếm.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        if (!$this->has('keyword') && $this->has('search')) {
            $this->merge(['keyword' => $this->get('search')]);
        }
    }
}
