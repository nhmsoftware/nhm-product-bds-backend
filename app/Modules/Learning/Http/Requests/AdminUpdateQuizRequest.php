<?php

namespace App\Modules\Learning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Core\Traits\HandleApi;

class AdminUpdateQuizRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'question' => 'nullable|string',
            'options' => 'nullable|array|min:2',
            'options.*' => 'required_with:options|string',
            'correct_option' => 'nullable|integer|min:0',
        ];
    }
}
