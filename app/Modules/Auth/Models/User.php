<?php

declare(strict_types=1);

namespace App\Modules\Auth\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;

use App\Modules\Auth\Models\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\HasDatabaseNotifications;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Tymon\JWTAuth\Contracts\JWTSubject;

use OpenApi\Attributes as OA;

/**
 * Class User
 *
 * @property string $id
 * @property string $staff_code
 * @property string|null $cccd
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
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Modules\Area\Models\LotDepositRequest[] $lotDepositRequests
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Modules\SiteTour\Models\SiteTour[] $siteTours
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Modules\CustomerMeeting\Models\CustomerMeeting[] $customerMeetings
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Modules\EmployeeReferral\Models\ReferralHistory[] $referrals
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Modules\Attendance\Models\Attendance[] $attendances
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection $notifications Danh sách thông báo của người dùng
 * @property-read int $unread_notifications_count Số thông báo chưa đọc
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
class User extends Authenticatable implements FilamentUser, JWTSubject
{
    use HasFactory, Notifiable, HasDatabaseNotifications, SoftDeletes, HasUuids;

    protected $fillable = [
        'staff_code',
        'cccd',
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


    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, [
            UserRole::MANAGER,
            UserRole::DIRECTOR,
            UserRole::CEO,
            UserRole::SUPER_ADMIN,
        ], true);
    }

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

    /**
     * Danh sách yêu cầu đặt cọc (giao dịch) của nhân viên.
     */
    public function lotDepositRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Modules\Area\Models\LotDepositRequest::class, 'user_id');
    }

    /**
     * Danh sách lượt dẫn khách tham quan dự án.
     */
    public function siteTours(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Modules\SiteTour\Models\SiteTour::class, 'user_id');
    }

    /**
     * Danh sách lượt gặp khách hàng.
     */
    public function customerMeetings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Modules\CustomerMeeting\Models\CustomerMeeting::class, 'user_id');
    }

    /**
     * Danh sách lịch sử giới thiệu (tuyển dụng/khách hàng) của nhân viên này (với vai trò người giới thiệu).
     */
    public function referrals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Modules\EmployeeReferral\Models\ReferralHistory::class, 'referrer_id');
    }

    /**
     * Danh sách chấm công của nhân viên.
     */
    public function attendances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Modules\Attendance\Models\Attendance::class, 'user_id');
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
