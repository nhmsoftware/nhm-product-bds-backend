<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Core\Traits\HandleApi;

class GetDepartmentRankingRequest extends FormRequest
{
    use HandleApi;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'month' => 'nullable|integer|min:1|max:12',
            'quarter' => 'nullable|integer|min:1|max:4',
            'year' => 'nullable|integer|min:2000|max:2100',
            'area' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    /**
     * Get the validation error messages.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'month.integer' => 'Tháng phải là số nguyên.',
            'month.min' => 'Tháng phải lớn hơn hoặc bằng 1.',
            'month.max' => 'Tháng phải nhỏ hơn hoặc bằng 12.',
            'quarter.integer' => 'Quý phải là số nguyên.',
            'quarter.min' => 'Quý phải lớn hơn hoặc bằng 1.',
            'quarter.max' => 'Quý phải nhỏ hơn hoặc bằng 4.',
            'year.integer' => 'Năm phải là số nguyên.',
            'year.min' => 'Năm phải từ 2000 trở đi.',
            'year.max' => 'Năm phải nhỏ hơn hoặc bằng 2100.',
            'area.string' => 'Khu vực phải là chuỗi ký tự.',
            'area.max' => 'Khu vực không được vượt quá 255 ký tự.',
            'per_page.integer' => 'Số lượng trên trang phải là số nguyên.',
            'per_page.min' => 'Số lượng trên trang phải lớn hơn 0.',
            'per_page.max' => 'Số lượng trên trang không được vượt quá 100.',
        ];
    }
}
