<?php

namespace App\Modules\Recruitment\DTO;

use App\Core\Requests\ListRequest;

class ListRecruitmentPostRequest extends ListRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'search' => 'nullable|string',
            'status' => 'nullable|integer|in:1,2',
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'status.in' => 'Trạng thái không hợp lệ.',
            'status.integer' => 'Trạng thái phải là số nguyên.',
        ]);
    }
}
