<?php

declare(strict_types=1);

namespace App\Modules\Auth\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class RolePermission extends Pivot
{
    use HasUuids;

    protected $table = 'role_permissions';
    
    public $incrementing = false;
    
    protected $keyType = 'string';
}
