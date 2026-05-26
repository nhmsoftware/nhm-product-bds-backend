<?php

declare(strict_types=1);

namespace App\Modules\EmployeeReferral\Interfaces;

use App\Core\DTOs\FilterDTO;
use App\Core\Services\ServiceReturn;
use App\Modules\EmployeeReferral\DTO\ScanReferralDTO;

interface ReferralHistoryServiceInterface
{
    /**
     * Ghi nhận lượt quét mã QR giới thiệu (Public).
     *
     * @param ScanReferralDTO $dto
     * @return ServiceReturn
     */
    public function recordScan(ScanReferralDTO $dto): ServiceReturn;

    /**
     * Lấy danh sách lịch sử giới thiệu của nhân viên.
     *
     * @param string $userId
     * @param FilterDTO $filter
     * @return ServiceReturn
     */
    public function getHistory(string $userId, FilterDTO $filter): ServiceReturn;

    /**
     * Xem chi tiết một bản ghi giới thiệu.
     *
     * @param string $userId
     * @param string $referralId
     * @return ServiceReturn
     */
    public function getDetail(string $userId, string $referralId): ServiceReturn;
}
