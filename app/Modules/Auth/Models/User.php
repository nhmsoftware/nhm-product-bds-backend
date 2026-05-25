<?php

declare(strict_types=1);

namespace App\Modules\Auth\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;

use App\Modules\Auth\Models\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Tymon\JWTAuth\Contracts\JWTSubject;

use OpenApi\Attributes as OA;

/**
 * Class User
 *
 * @property string $id
 * @property string $staff_code
 * @property string $name
 * @property string $email
 * @property string $phone
 * @property string $password
 * @property UserRole $role
 * @property string $avatar
 * @property string $address
 * @property string $department
 * @property string $job_position
 * @property string $area
 * @property string $fcm_token
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read EmployeeProfile|null $employeeProfile
 * @mixin \Eloquent
 */
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
        new OA\Property(property: 'role', type: 'integer', example: \App\Modules\Auth\Models\Enums\UserRole::EMPLOYEE->value),
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
        'role' => UserRole::class,
    ];

    // ─── Relationships ───────────────────────────────────────────

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
    : HasOne {
        return $this->hasOne(EmployeeProfile::class, 'user_id');
    }

    /**
     * Danh sách khu đất được phân quyền cho người dùng.
     */
    public function assignedAreas(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            \App\Modules\Area\Models\Area::class,
            'area_assignments',
            'user_id',
            'area_id'
        )->withTimestamps()->whereNull('area_assignments.deleted_at');
    }

    public function setRoleAttribute($value)
    {
        if ($value === null) {
            $this->attributes['role'] = null;
            return;
        }
        $this->attributes['role'] = $value instanceof UserRole ? $value->value : UserRole::deserialize($value)->value;
    }

    public function toArray()
    {
        $array = parent::toArray();
        if (isset($array['role']) && $this->role instanceof UserRole) {
            $array['role'] = $this->role->serialize();
        }
        return $array;
    }
}
