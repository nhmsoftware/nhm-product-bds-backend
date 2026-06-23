<?php

namespace App\Modules\Recruitment\Models;

use App\Modules\Auth\Models\User;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Modules\Branch\Models\Branch;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecruitmentApplication extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'recruitment_applications';

    protected $fillable = [
        'user_id',
        'applied_position',
        'applied_branch_id',
        'status',
        'cv_url',
        'introduction',
        'education',
        'experience',
        'profile_url',
        'approved_by',
        'rejected_reason',
        'processed_at',
    ];

    protected $casts = [
        'applied_position' => UserRole::class,
        'processed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function appliedBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'applied_branch_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
