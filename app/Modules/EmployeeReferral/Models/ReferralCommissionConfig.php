<?php

declare(strict_types=1);

namespace App\Modules\EmployeeReferral\Models;

use App\Modules\EmployeeReferral\Models\Enums\ReferralType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use OpenApi\Attributes as OA;

/**
 * Class ReferralCommissionConfig
 *
 * @property string $id
 * @property ReferralType $referral_type
 * @property int|string $amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'ReferralCommissionConfig',
    title: 'Referral Commission Config Model',
    description: 'Cấu hình hoa hồng giới thiệu',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'uuid-string'),
        new OA\Property(property: 'referral_type', type: 'integer', example: \App\Modules\EmployeeReferral\Models\Enums\ReferralType::RECRUITMENT->value, description: '1: QR Tuyển dụng, 2: QR Giới thiệu khách hàng'),
        new OA\Property(property: 'amount', type: 'string', example: '500000', description: 'Số tiền hoa hồng (trả về dạng string)'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class ReferralCommissionConfig extends Model
{
    use HasUuids;

    protected $table = 'referral_commission_configs';

    protected $fillable = [
        'referral_type',
        'amount',
    ];

    protected $casts = [
        'referral_type' => ReferralType::class,
    ];

    // ─── Relationships ───────────────────────────────────────────

    // ─── Helpers ─────────────────────────────────────────────────

    public function toArray()
    {
        $array = parent::toArray();
        if (isset($array['referral_type']) && $this->referral_type instanceof ReferralType) {
            $array['referral_type'] = $this->referral_type->value;
        }
        if (isset($array['amount'])) {
            $array['amount'] = (string) $this->amount;
        }
        return $array;
    }
}
