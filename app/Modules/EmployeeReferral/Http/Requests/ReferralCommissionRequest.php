<?php

declare(strict_types=1);

namespace App\Modules\EmployeeReferral\Http\Requests;

use App\Core\Requests\ListRequest;
use App\Modules\EmployeeReferral\Models\Enums\CommissionPaymentStatus;
use App\Modules\EmployeeReferral\Models\Enums\ReferralType;

class ReferralCommissionRequest extends ListRequest
{
    protected int $defaultPerPage = 10;
    protected string $defaultSortBy = 'created_at';
    protected string $defaultDirection = 'desc';

    protected array $allowedSorts = ['created_at', 'amount'];
    protected array $allowedFilters = ['referral_type', 'status', 'search'];

    public function rules(): array
    {
        $rules = parent::rules();

        $validTypes = [];
        foreach (ReferralType::cases() as $case) {
            $validTypes[] = $case->value;
            $validTypes[] = (string) $case->value;
        }

        $validStatuses = [];
        foreach (CommissionPaymentStatus::cases() as $case) {
            $validStatuses[] = $case->value;
            $validStatuses[] = (string) $case->value;
        }

        $rules['filters.referral_type'] = ['sometimes', 'nullable', 'in:' . implode(',', $validTypes)];
        $rules['filters.status']        = ['sometimes', 'nullable', 'in:' . implode(',', $validStatuses)];
        $rules['filters.search']        = ['sometimes', 'nullable', 'string', 'max:255'];

        return $rules;
    }
}
