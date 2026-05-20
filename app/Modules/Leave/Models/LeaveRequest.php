<?php

namespace App\Modules\Leave\Models;

use App\Modules\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use OpenApi\Attributes as OA;

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
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'approved', 'rejected'], example: 'pending', description: 'Trạng thái duyệt: pending (đang chờ), approved (đã duyệt), rejected (từ chối)'),
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
    ];

    /**
     * Thiết lập quan hệ liên kết với Model User (Nhân viên).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
