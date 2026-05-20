<?php

namespace App\Modules\Auth\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Tymon\JWTAuth\Contracts\JWTSubject;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'User',
    title: 'User Model',
    description: 'Thông tin người dùng',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'uuid-string'),
        new OA\Property(property: 'staff_code', type: 'string', example: 'ST-ABCXYZ'),
        new OA\Property(property: 'name', type: 'string', example: 'Nguyen Van A'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'nguyenvana@example.com'),
        new OA\Property(property: 'phone', type: 'string', example: '0901234567'),
        new OA\Property(property: 'role', type: 'string', example: 'agent'),
        new OA\Property(property: 'avatar', type: 'string', nullable: true),
        new OA\Property(property: 'address', type: 'string', nullable: true, example: '123 Đường ABC, Quận 1, TP. HCM'),
        new OA\Property(property: 'department', type: 'string', nullable: true, example: 'Kinh doanh', description: 'Phòng ban của nhân viên'),
        new OA\Property(property: 'job_position', type: 'string', nullable: true, example: 'Nhân viên kinh doanh', description: 'Vị trí công việc'),
        new OA\Property(property: 'area', type: 'string', nullable: true, example: 'Miền Nam', description: 'Khu vực quản lý/khu vực làm việc'),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
    ]
)]
class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes, HasUuids;

    protected $fillable = [
        'staff_code',
        'name',
        'email',
        'phone',
        'password',
        'role',
        'avatar',
        'address',
        'department',
        'job_position',
        'area',
        'fcm_token',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Quan hệ tới chi tiết hồ sơ nhân sự.
     */
    public function employeeProfile()
    {
        return $this->hasOne(EmployeeProfile::class, 'user_id');
    }
}
