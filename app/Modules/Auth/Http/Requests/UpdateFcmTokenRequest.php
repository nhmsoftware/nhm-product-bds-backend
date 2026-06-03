<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Requests;

use App\Core\Http\Requests\BaseRequest;

class UpdateFcmTokenRequest extends BaseRequest
{
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'fcm_token' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fcm_token.required' => 'FCM Token không được để trống.',
            'fcm_token.string' => 'FCM Token phải là chuỗi.',
            'fcm_token.max' => 'FCM Token quá dài.',
        ];
    }
}
