<?php

declare(strict_types=1);

namespace App\Modules\Auth\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Modules\Auth\Models\Role;
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
 * @property string|null $role_id
 * @property Role|null $role
 * @property string $avatar
 * @property string $address
 * @property string $department
 * @property string $job_position
 * @property string $area
 * @property string|null $branch_id
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
        new OA\Property(property: 'role_id', type: 'string', format: 'uuid', nullable: true, description: 'ID vai trò'),
        new OA\Property(property: 'avatar', type: 'string', nullable: true),
        new OA\Property(property: 'address', type: 'string', nullable: true, example: '123 Đường ABC, Quận 1, TP. HCM'),
        new OA\Property(property: 'department', type: 'string', nullable: true, example: 'Kinh doanh', description: 'Phòng ban của nhân viên'),
        new OA\Property(property: 'job_position', type: 'string', nullable: true, example: 'Nhân viên kinh doanh', description: 'Vị trí công việc'),
        new OA\Property(property: 'area', type: 'string', nullable: true, example: 'Hà Nội', description: 'Khu vực địa lý làm việc'),
        new OA\Property(property: 'branch_id', type: 'string', format: 'uuid', nullable: true, example: 'uuid-string', description: 'ID chi nhánh công ty'),
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
        'role_id',
        'avatar',
        'address',
        'department',
        'job_position',
        'department_id',
        'job_position_id',
        'branch_id',
        'fcm_token',
        'is_active',
        'locked_at',
        'lock_reason',
        'lock_days',
        'lock_expires_at',
        'locked_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'role_id' => 'string',
        'department_id' => 'string',
        'job_position_id' => 'integer',
        'locked_at' => 'datetime',
        'lock_days' => 'integer',
        'lock_expires_at' => 'datetime',
    ];

    protected $appends = [
        'department',
        'branch',
        'job_position',
        'role_name',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        if (!$this->role || !$this->role->is_active) {
            return false;
        }

        return ! in_array($this->role->name, ['ctv', 'buyer']);
    }

    // ─── Role & Permission Helpers ────────────────────────────────

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function hasPermission(string $permission): bool
    {
        if (!$this->role) {
            return false;
        }

        $permRecord = \App\Modules\Auth\Models\Permission::where('name', $permission)->first();
        if ($permRecord && $permRecord->module === 'mobile') {
            if ($this->role->hasPermission('manage_all_mobile')) {
                return true;
            }
        } else {
            if ($this->role->hasManageAll()) {
                return true;
            }
        }

        return $this->role->hasPermission($permission);
    }

    public function hasAnyPermission(array $permissions): bool
    {
        if (!$this->role) {
            return false;
        }

        foreach ($permissions as $perm) {
            if ($this->hasPermission($perm)) {
                return true;
            }
        }

        return false;
    }

    public function getRoleNameAttribute(): ?string
    {
        return $this->role?->label;
    }

    /**
     * Backward-compatible accessor: $user->role returns Role model.
     * Code checking $user->role->name or $user->role?->name will work.
     */
    public function getRoleAttribute(): ?Role
    {
        return $this->getRelationValue('role') ?: $this->role()->first();
    }

    // ─── Relationships ───────────────────────────────────────────

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Branch\Models\Branch::class, 'branch_id');
    }

    public function getBranchAttribute(): ?string
    {
        $branchModel = $this->getRelationValue('branch');
        if (!$branchModel && $this->branch_id) {
            $branchModel = $this->branch()->first();
            if ($branchModel) {
                $this->setRelation('branch', $branchModel);
            }
        }
        return $branchModel?->name;
    }

    public function employeeProfile(): HasOne
    {
        return $this->hasOne(EmployeeProfile::class, 'user_id');
    }

    public function assignedAreas(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            \App\Modules\Area\Models\Area::class,
            'area_assignments',
            'user_id',
            'area_id'
        )->withTimestamps()->whereNull('area_assignments.deleted_at');
    }

    public function lotDepositRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Modules\Area\Models\LotDepositRequest::class, 'user_id');
    }

    public function siteTours(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Modules\SiteTour\Models\SiteTour::class, 'user_id');
    }

    public function customerMeetings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Modules\CustomerMeeting\Models\CustomerMeeting::class, 'user_id');
    }

    public function referrals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Modules\EmployeeReferral\Models\ReferralHistory::class, 'referrer_id');
    }

    public function attendances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Modules\Attendance\Models\Attendance::class, 'user_id');
    }

    public function departmentRel(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function jobPosition(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(JobPosition::class, 'job_position_id');
    }

    public function getDepartmentAttribute(): ?string
    {
        $relation = $this->getRelationValue('departmentRel');
        if (!$relation && $this->department_id) {
            $relation = $this->departmentRel()->first();
            if ($relation) {
                $this->setRelation('departmentRel', $relation);
            }
        }
        return $relation?->name;
    }

    public function getJobPositionAttribute(): ?string
    {
        $relation = $this->getRelationValue('jobPosition');
        if (!$relation && $this->job_position_id) {
            $relation = $this->jobPosition()->first();
            if ($relation) {
                $this->setRelation('jobPosition', $relation);
            }
        }
        return $relation?->name;
    }

    public function setDepartmentAttribute(?string $value): void
    {
        if (empty($value)) {
            $this->attributes['department_id'] = null;
            return;
        }

        $dept = Department::where('name', $value)->first();
        if ($dept) {
            $this->attributes['department_id'] = $dept->id;
            if ($dept->branch_id) {
                $this->attributes['branch_id'] = $dept->branch_id;
            }
        }
    }

    public function setJobPositionAttribute(mixed $value): void
    {
        if (empty($value)) {
            $this->attributes['job_position_id'] = null;
            return;
        }

        if (is_numeric($value)) {
            $this->attributes['job_position_id'] = (int) $value;
            return;
        }

        $pos = JobPosition::where('name', $value)->orWhere('code', $value)->first();
        if ($pos) {
            $this->attributes['job_position_id'] = $pos->id;
        }
    }

    // ─── Lock / Unlock ──────────────────────────────────────────

    public function isLocked(): bool
    {
        if (is_null($this->locked_at)) {
            return false;
        }

        if (! is_null($this->lock_expires_at) && $this->lock_expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function lock(string $reason, ?int $days, User $lockedBy): void
    {
        $days = max(1, $days ?? 2);
        $now = now();

        $this->update([
            'locked_at' => $now,
            'lock_reason' => $reason,
            'lock_days' => $days,
            'lock_expires_at' => $now->copy()->addDays($days),
            'locked_by' => $lockedBy->id,
            'is_active' => false,
        ]);
    }

    public function unlock(): void
    {
        $this->update([
            'locked_at' => null,
            'lock_reason' => null,
            'lock_days' => null,
            'lock_expires_at' => null,
            'locked_by' => null,
            'is_active' => true,
        ]);
    }

    public function getAreaAttribute(): ?string
    {
        return $this->branch;
    }

    public function toArray()
    {
        $array = parent::toArray();
        $array['role'] = $this->role?->name;
        $array['role_label'] = $this->role?->label;
        $array['role_id'] = $this->role_id;
        $array['branch'] = $this->branch;
        $array['branch_name'] = $this->branch;
        $array['branch_id'] = $this->branch_id;
        $array['department'] = $this->department;
        $array['job_position'] = $this->job_position;

        // Append user permissions
        if ($this->role) {
            $perms = collect();
            
            // Check System Super Admin (manage_all)
            if ($this->role->hasPermission('manage_all')) {
                $systemPerms = \App\Modules\Auth\Models\Permission::where('module', '!=', 'mobile')->pluck('name');
                $perms = $perms->merge($systemPerms);
            }
            
            // Check Mobile Super Admin (manage_all_mobile)
            if ($this->role->hasPermission('manage_all_mobile')) {
                $mobilePerms = \App\Modules\Auth\Models\Permission::where('module', 'mobile')->pluck('name');
                $perms = $perms->merge($mobilePerms);
            }
            
            // Merge explicitly assigned permissions
            $assignedPerms = $this->role->permissions->pluck('name');
            $perms = $perms->merge($assignedPerms)->unique()->values();
            
            $array['permissions'] = $perms->toArray();
        } else {
            $array['permissions'] = [];
        }

        return $array;
    }
}
