<?php

namespace App\Modules\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Team extends Model
{
    use SoftDeletes, HasUuids;

    protected $table = 'teams';

    protected $fillable = [
        'name',
        'code',
        'department_id',
        'leader_id',
        'is_active',
    ];

    protected $casts = [
        'id' => 'string',
        'department_id' => 'string',
        'leader_id' => 'string',
        'is_active' => 'boolean',
    ];

    public function department(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function leader(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function members(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(User::class, 'team_id');
    }
}
