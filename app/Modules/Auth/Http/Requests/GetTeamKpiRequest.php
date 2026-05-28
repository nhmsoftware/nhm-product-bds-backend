<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Core\Traits\HandleApi;

class GetTeamKpiRequest extends FormRequest
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
            'search' => 'nullable|string|max:255',
            'job_position' => 'nullable|string|max:255',
            'from_date' => 'nullable|date|date_format:Y-m-d',
            'to_date' => 'nullable|date|date_format:Y-m-d|after_or_equal:from_date',
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
            'search.string' => 'Từ khóa tìm kiếm phải là chuỗi ký tự.',
            'search.max' => 'Từ khóa tìm kiếm không được vượt quá 255 ký tự.',
            'job_position.string' => 'Vị trí công việc phải là chuỗi ký tự.',
            'job_position.max' => 'Vị trí công việc không được vượt quá 255 ký tự.',
            'from_date.date' => 'Ngày bắt đầu không đúng định dạng ngày.',
            'from_date.date_format' => 'Ngày bắt đầu phải theo định dạng YYYY-MM-DD.',
            'to_date.date' => 'Ngày kết thúc không đúng định dạng ngày.',
            'to_date.date_format' => 'Ngày kết thúc phải theo định dạng YYYY-MM-DD.',
            'to_date.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',
            'per_page.integer' => 'Số lượng trên trang phải là số nguyên.',
            'per_page.min' => 'Số lượng trên trang phải lớn hơn 0.',
            'per_page.max' => 'Số lượng trên trang không được vượt quá 100.',
        ];
    }
}
