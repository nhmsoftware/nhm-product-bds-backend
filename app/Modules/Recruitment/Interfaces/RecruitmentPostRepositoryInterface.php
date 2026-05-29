<?php

namespace App\Modules\Recruitment\Interfaces;

use Illuminate\Pagination\LengthAwarePaginator;

interface RecruitmentPostRepositoryInterface
{
    /**
     * Lấy danh sách bài tuyển dụng có phân trang và lọc
     *
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getFiltered(array $filters): LengthAwarePaginator;
}
