<?php

declare(strict_types=1);

namespace App\Modules\Leave\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Modules\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use OpenApi\Attributes as OA;
use App\Modules\Leave\Models\Enums\RequestStatus;/**
 * Class LeaveRequest
 *
 * @property string $id
 * @property string $user_id
 * @property string|null $approver_id
 * @property LeaveType $leave_type
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property string $reason
 * @property RequestStatus $status
 * @property string $rejection_reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read User|null $user
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'LeaveRequest',
    title: 'LeaveRequest Model',
    description: 'Thông tin yêu cầu xin nghỉ phép của nhân viên',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b1234567', description: 'ID duy nhất của đơn nghỉ phép'),
        new OA\Property(property: 'user_id', type: 'string', format: 'uuid', example: 'a1b2c3d4-e5f6-7a8b-9c0d-1e2f3a4b5c6d', description: 'ID của nhân viên'),
        new OA\Property(
            property: 'leave_type',
            type: 'string',
            enum: ['annual', 'unpaid', 'personal', 'maternity', 'business', 'compensatory'],
            example: 'annual',
            description: 'Loại nghỉ phép: annual (phép năm), unpaid (không lương), personal (cá nhân), maternity (thai sản), business (công tác), compensatory (bù)'
        ),
        new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2026-05-20', description: 'Ngày bắt đầu nghỉ phép (Y-m-d)'),
        new OA\Property(property: 'end_date', type: 'string', format: 'date', example: '2026-05-22', description: 'Ngày kết thúc nghỉ phép (Y-m-d)'),
        new OA\Property(property: 'reason', type: 'string', example: 'Có việc gia đình đột xuất cần giải quyết', description: 'Lý do nghỉ'),
        new OA\Property(property: 'status', type: 'integer', example: \App\Modules\Leave\Models\Enums\RequestStatus::PENDING->value, description: 'Trạng thái duyệt: 1 (pending), 2 (approved), 3 (rejected)'),
        new OA\Property(property: 'rejection_reason', type: 'string', nullable: true, example: 'Không đủ phép năm', description: 'Lý do từ chối (nếu có)'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-05-19T08:15:23+07:00'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-05-19T08:15:23+07:00'),
    ]
)]
class LeaveRequest extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'leave_requests';

    protected $fillable = [
        'user_id',
        'approver_id',
        'leave_type',
        'start_date',
        'end_date',
        'reason',
        'status',
        'rejection_reason',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'leave_type' => \App\Modules\Leave\Enums\LeaveType::class,
        'status' => RequestStatus::class,
    ];

    // ─── Relationships ───────────────────────────────────────────

    /**
     * Thiết lập quan hệ liên kết với Model User (Nhân viên).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    : BelongsTo {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function setLeaveTypeAttribute($value)
    {
        if ($value === null) {
            $this->attributes['leave_type'] = null;
            return;
        }
        $this->attributes['leave_type'] = $value instanceof \App\Modules\Leave\Enums\LeaveType ? $value->value : \App\Modules\Leave\Enums\LeaveType::deserialize($value)->value;
    }

    public function setStatusAttribute($value)
    {
        if ($value === null) {
            $this->attributes['status'] = null;
            return;
        }
        $this->attributes['status'] = $value instanceof RequestStatus ? $value->value : RequestStatus::deserialize($value)->value;
    }

    public function toArray()
    {
        $array = parent::toArray();
        if (isset($array['leave_type']) && $this->leave_type instanceof \App\Modules\Leave\Enums\LeaveType) {
            $array['leave_type'] = $this->leave_type->serialize();
        }
        if (isset($array['status']) && $this->status instanceof RequestStatus) {
            $array['status'] = $this->status->serialize();
        }
        return $array;
    }
}
