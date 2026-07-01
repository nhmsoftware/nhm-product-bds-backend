<?php

declare(strict_types=1);

namespace App\Modules\Auth\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Model Permission - Quyền hạn chi tiết trong hệ thống.
 *
 * @property string $id
 * @property string $name        Slug: approve_onboard, manage_contracts...
 * @property string $label       Hiển thị: "Duyệt onboarding"...
 * @property string|null $module Nhóm module: attendance, leave, onboarding...
 * @property bool $is_active
 */
class Permission extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = true;

    protected $fillable = [
        'name',
        'label',
        'module',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ─── Relationships ───────────────────────────────────────────

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'role_permissions',
            'permission_id',
            'role_id'
        )->using(RolePermission::class)->withTimestamps();
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('module')->orderBy('name');
    }
}
