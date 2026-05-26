<?php

declare(strict_types=1);

namespace App\Modules\EmployeeReferral\Models;

use App\Modules\Auth\Models\User;
use App\Modules\EmployeeReferral\Models\Enums\CommissionPaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use OpenApi\Attributes as OA;

/**
 * Class ReferralCommission
 *
 * @property string $id
 * @property string $referrer_id
 * @property string $referral_history_id
 * @property int $amount
 * @property CommissionPaymentStatus $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read User|null $referrer
 * @property-read ReferralHistory|null $referralHistory
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'ReferralCommission',
    title: 'Referral Commission Model',
    description: 'Thông tin hoa hồng giới thiệu của nhân viên',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'uuid-string'),
        new OA\Property(property: 'referrer_id', type: 'string', format: 'uuid', description: 'ID của nhân viên nhận hoa hồng'),
        new OA\Property(property: 'referral_history_id', type: 'string', format: 'uuid', description: 'ID của lượt giới thiệu phát sinh hoa hồng'),
        new OA\Property(property: 'amount', type: 'string', example: '500000', description: 'Số tiền hoa hồng (trả về dạng string để tránh mất mát dữ liệu do giới hạn độ lớn của JS)'),
        new OA\Property(property: 'status', type: 'integer', example: \App\Modules\EmployeeReferral\Models\Enums\CommissionPaymentStatus::PENDING->value),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class ReferralCommission extends Model
{
    use SoftDeletes, HasUuids;

    protected $table = 'referral_commissions';

    protected $fillable = [
        'referrer_id',
        'referral_history_id',
        'amount',
        'status',
    ];

    protected $casts = [
        'status' => CommissionPaymentStatus::class,
    ];

    // ─── Relationships ───────────────────────────────────────────

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referralHistory(): BelongsTo
    {
        return $this->belongsTo(ReferralHistory::class, 'referral_history_id');
    }

    // ─── Helpers ─────────────────────────────────────────────────

    public function toArray()
    {
        $array = parent::toArray();
        if (isset($array['status']) && $this->status instanceof CommissionPaymentStatus) {
            $array['status'] = $this->status->serialize();
        }
        if (isset($array['amount'])) {
            $array['amount'] = (string) $this->amount;
        }
        return $array;
    }
}
