<?php

namespace App\Modules\DepartmentTransfer\Models;

use App\Modules\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'DepartmentTransferRequest',
    title: 'DepartmentTransferRequest Model',
    description: 'Thông tin yêu cầu chuyển phòng ban',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b1234567'),
        new OA\Property(property: 'user_id', type: 'string', format: 'uuid', example: 'a1b2c3d4-e5f6-7a8b-9c0d-1e2f3a4b5c6d'),
        new OA\Property(property: 'current_department', type: 'string', example: 'Phòng Kỹ thuật'),
        new OA\Property(property: 'target_department', type: 'string', example: 'Phòng Kinh doanh'),
        new OA\Property(property: 'reason', type: 'string', example: 'Muốn thử thách ở lĩnh vực mới'),
        new OA\Property(property: 'desired_transfer_date', type: 'string', format: 'date', example: '2026-06-01'),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'approved', 'rejected'], example: 'pending'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class DepartmentTransferRequest extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'department_transfer_requests';

    protected $fillable = [
        'user_id',
        'current_department',
        'target_department',
        'reason',
        'desired_transfer_date',
        'status',
        'rejection_reason',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'desired_transfer_date' => 'date:Y-m-d',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
