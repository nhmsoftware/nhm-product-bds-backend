<?php

declare(strict_types=1);

namespace App\Modules\Area\Http\Requests;

use App\Core\Requests\ListRequest;

final class ListAreaRequest extends ListRequest
{
    protected int $defaultPerPage = 10;
    protected string $defaultSortBy = 'created_at';
    protected string $defaultDirection = 'desc';

    protected array $allowedSorts = ['id', 'created_at', 'name', 'total_lots', 'remaining_lots'];
    protected array $allowedFilters = ['is_featured'];

    /**
     * Bổ sung các rules kiểm tra tính hợp lệ cụ thể cho bộ lọc khu đất.
     *
     * @return array
     */
    public function rules(): array
    {
        $rules = parent::rules();

        $rules['filters.is_featured'] = ['sometimes', 'nullable', 'string', 'in:true,false,1,0'];

        return $rules;
    }
}
