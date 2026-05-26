<?php

declare(strict_types=1);

namespace App\Modules\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ProjectAssignment extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'project_assignments';

    protected $fillable = [
        'project_id',
        'assignable_id',
        'assignable_type',
        'permissions',
    ];

    protected $casts = [
        'id' => 'string',
        'project_id' => 'string',
        'assignable_id' => 'string',
        'permissions' => 'array',
    ];

    public function project(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function assignable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }
}
