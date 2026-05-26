<?php

declare(strict_types=1);

namespace App\Modules\EmployeeReferral\Models;

use App\Modules\Auth\Models\User;
use App\Modules\EmployeeReferral\Models\Enums\ReferralStatus;
use App\Modules\EmployeeReferral\Models\Enums\ReferralType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use OpenApi\Attributes as OA;

/**
 * Class ReferralHistory
 *
 * @property string $id
 * @property string $referrer_id
 * @property string|null $referee_id
 * @property string $name
 * @property string $phone
 * @property ReferralType $referral_type
 * @property ReferralStatus $status
 * @property \Illuminate\Support\Carbon $scanned_at
 * @property \Illuminate\Support\Carbon|null $registered_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read User|null $referrer
 * @property-read User|null $referee
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'ReferralHistory',
    title: 'Referral History Model',
    description: 'Lịch sử người dùng quét mã QR giới thiệu',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'uuid-string'),
        new OA\Property(property: 'referrer_id', type: 'string', format: 'uuid', description: 'ID của nhân viên giới thiệu'),
        new OA\Property(property: 'referee_id', type: 'string', format: 'uuid', nullable: true, description: 'ID của người đăng ký'),
        new OA\Property(property: 'name', type: 'string', example: 'Nguyễn Văn Khách'),
        new OA\Property(property: 'phone', type: 'string', example: '0901234567'),
        new OA\Property(property: 'referral_type', type: 'integer', example: \App\Modules\EmployeeReferral\Models\Enums\ReferralType::RECRUITMENT->value),
        new OA\Property(property: 'status', type: 'integer', example: \App\Modules\EmployeeReferral\Models\Enums\ReferralStatus::INCOMPLETE->value),
        new OA\Property(property: 'scanned_at', type: 'string', format: 'date-time', example: '2026-05-26T10:00:00Z'),
        new OA\Property(property: 'registered_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class ReferralHistory extends Model
{
    use SoftDeletes, HasUuids;

    protected $table = 'referral_histories';

    protected $fillable = [
        'referrer_id',
        'referee_id',
        'name',
        'phone',
        'referral_type',
        'status',
        'scanned_at',
        'registered_at',
    ];

    protected $casts = [
        'referral_type' => ReferralType::class,
        'status' => ReferralStatus::class,
        'scanned_at' => 'datetime',
        'registered_at' => 'datetime',
    ];

    // ─── Relationships ───────────────────────────────────────────

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referee_id');
    }

    // ─── Helpers ─────────────────────────────────────────────────

    public function isRegistered(): bool
    {
        return $this->status === ReferralStatus::REGISTERED;
    }

    public function toArray()
    {
        $array = parent::toArray();
        if (isset($array['referral_type']) && $this->referral_type instanceof ReferralType) {
            $array['referral_type'] = $this->referral_type->serialize();
        }
        if (isset($array['status']) && $this->status instanceof ReferralStatus) {
            $array['status'] = $this->status->serialize();
        }
        return $array;
    }
}
