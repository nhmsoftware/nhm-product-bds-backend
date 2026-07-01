<?php

declare(strict_types=1);

namespace App\Modules\Auth\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model Role - Vai trò động trong hệ thống.
 *
 * Thay thế cho UserRole enum cứng, cho phép admin CRUD role qua Filament.
 *
 * @property string $id
 * @property string $name        Slug: super_admin, gdcn, gdkd...
 * @property string $label       Hiển thị: "Giám đốc chi nhánh"...
 * @property string|null $description
 * @property int $level          Thứ bậc: 0=cao nhất
 * @property bool $is_system     Role hệ thống không xóa/sửa name
 * @property int $sort
 * @property bool $is_active
 */
class Role extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'label',
        'description',
        'level',
        'is_system',
        'sort',
        'is_active',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'level' => 'integer',
        'sort' => 'integer',
    ];

    // ─── Relationships ───────────────────────────────────────────

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'role_permissions',
            'role_id',
            'permission_id'
        )->using(RolePermission::class)->withTimestamps();
    }

    public function users()
    {
        return $this->hasMany(User::class, 'role_id');
    }

    // ─── Helpers ─────────────────────────────────────────────────

    /**
     * Kiểm tra role có permission cụ thể không.
     */
    public function hasPermission(string $permission): bool
    {
        return $this->permissions()->where('name', $permission)->exists();
    }

    /**
     * Kiểm tra role có bất kỳ permission nào trong danh sách không.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        return $this->permissions()->whereIn('name', $permissions)->exists();
    }

    /**
     * Kiểm tra role có permission manage_all (quyền cao nhất) không.
     */
    public function hasManageAll(): bool
    {
        return $this->hasPermission('manage_all');
    }

    /**
     * Scope: chỉ role đang active.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: sắp xếp theo thứ tự sort.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort')->orderBy('level');
    }
}
