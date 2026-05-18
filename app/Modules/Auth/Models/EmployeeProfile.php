<?php

namespace App\Modules\Auth\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'EmployeeProfile',
    title: 'Employee Profile Model',
    description: 'Bảng thông tin chi tiết hồ sơ nhân sự của hệ thống.',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '123e4567-e89b-12d3-a456-426614174000'),
        new OA\Property(property: 'user_id', type: 'string', format: 'uuid', example: '123e4567-e89b-12d3-a456-426614174000'),
        new OA\Property(property: 'employee_title', type: 'string', nullable: true, example: 'Nhân viên xuất sắc năm 2026'),
        new OA\Property(property: 'identity_card', type: 'string', nullable: true, example: '037123456789'),
        new OA\Property(property: 'dob', type: 'string', format: 'date', nullable: true, example: '1995-10-15'),
        new OA\Property(property: 'bank_account_name', type: 'string', nullable: true, example: 'NGUYEN VAN A'),
        new OA\Property(property: 'bank_account_number', type: 'string', nullable: true, example: '190345678910'),
        new OA\Property(property: 'bank_name', type: 'string', nullable: true, example: 'Techcombank'),
        new OA\Property(property: 'education', type: 'string', nullable: true, example: 'Đại học Bách Khoa TP.HCM'),
        new OA\Property(property: 'major', type: 'string', nullable: true, example: 'Công nghệ thông tin'),
        new OA\Property(property: 'experience', type: 'string', nullable: true, example: '3 năm kinh nghiệm lập trình Laravel'),
        new OA\Property(
            property: 'attachments',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'type', type: 'string', example: 'labor_contract'),
                    new OA\Property(property: 'name', type: 'string', example: 'Hop_Dong_Lao_Dong.pdf'),
                    new OA\Property(property: 'url', type: 'string', example: 'https://bds-app.s3.amazonaws.com/contracts/hopdong.pdf'),
                ],
                type: 'object'
            ),
            nullable: true
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-05-18T16:10:00Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-05-18T16:10:00Z'),
    ]
)]
class EmployeeProfile extends Model
{
    use HasUuids;

    protected $table = 'employee_profiles';

    protected $fillable = [
        'user_id',
        'employee_title',
        'identity_card',
        'dob',
        'bank_account_name',
        'bank_account_number',
        'bank_name',
        'education',
        'major',
        'experience',
        'attachments',
    ];

    protected $casts = [
        'dob'         => 'date',
        'attachments' => 'array',
    ];

    /**
     * Quan hệ tới User sở hữu hồ sơ nhân sự này.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
