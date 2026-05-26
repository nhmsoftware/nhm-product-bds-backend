<?php

declare(strict_types=1);

namespace App\Modules\EmployeeReferral\Http\Requests;

use App\Core\Requests\ListRequest;
use App\Modules\EmployeeReferral\Models\Enums\ReferralType;

class ReferralHistoryRequest extends ListRequest
{
    protected int $defaultPerPage = 10;
    protected string $defaultSortBy = 'scanned_at';
    protected string $defaultDirection = 'desc';

    protected array $allowedSorts = ['scanned_at', 'registered_at', 'created_at'];
    protected array $allowedFilters = ['referral_type', 'search'];

    public function rules(): array
    {
        $rules = parent::rules();

        $validTypes = [];
        foreach (ReferralType::cases() as $case) {
            $validTypes[] = $case->value;
            $validTypes[] = (string) $case->value;
        }

        $rules['filters.referral_type'] = ['sometimes', 'nullable', 'in:' . implode(',', $validTypes)];
        $rules['filters.search']        = ['sometimes', 'nullable', 'string', 'max:255'];

        return $rules;
    }
}
