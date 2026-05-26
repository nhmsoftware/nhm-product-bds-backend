<?php

declare(strict_types=1);

namespace App\Modules\EmployeeReferral\DTO;

use Illuminate\Http\Request;

final class ScanReferralDTO
{
    public function __construct(
        public readonly string $referral_code,
        public readonly int $referral_type,
        public readonly string $name,
        public readonly string $phone,
    ) {
    }

    /**
     * Khởi tạo DTO từ Request.
     *
     * @param Request $request
     * @return self
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            referral_code: $request->validated('referral_code'),
            referral_type: (int) $request->validated('referral_type'),
            name: $request->validated('name'),
            phone: $request->validated('phone')
        );
    }

    /**
     * Chuyển đổi DTO thành mảng.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'referral_code' => $this->referral_code,
            'referral_type' => $this->referral_type,
            'name'          => $this->name,
            'phone'         => $this->phone,
        ];
    }
}
