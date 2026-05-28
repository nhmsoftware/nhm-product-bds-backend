<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Core\Traits\HandleApi;

class GetRewardPointHistoryRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'from_date.date' => 'Ngày bắt đầu không đúng định dạng.',
            'to_date.date' => 'Ngày kết thúc không đúng định dạng.',
            'to_date.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',
            'per_page.integer' => 'Số lượng trên trang phải là số nguyên.',
            'per_page.min' => 'Số lượng trên trang phải lớn hơn 0.',
            'per_page.max' => 'Số lượng trên trang không được vượt quá 100.',
        ];
    }
}
