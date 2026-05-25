<?php

declare(strict_types=1);

namespace App\Modules\Area\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Core\Traits\HandleApi;

class RequestLockLotRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reason' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get the custom messages for validation errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'reason.max' => 'Lý do giữ chỗ không được vượt quá 1000 ký tự.',
        ];
    }
}
