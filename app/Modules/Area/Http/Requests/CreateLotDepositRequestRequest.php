<?php

declare(strict_types=1);

namespace App\Modules\Area\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Core\Traits\HandleApi;

class CreateLotDepositRequestRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true; // Authorize in service
    }

    public function rules(): array
    {
        return [
            'reason' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'reason.string' => 'Lý do phải là chuỗi ký tự.',
            'reason.max' => 'Lý do không được vượt quá 1000 ký tự.',
        ];
    }
}
