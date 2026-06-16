<?php

declare(strict_types=1);

namespace App\Modules\Area\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class InventorySetting extends Model
{
    use HasUuids;

    protected $table = 'inventory_settings';

    protected $fillable = ['key', 'value', 'updated_by'];

    protected $casts = [
        'id' => 'string',
        'value' => 'array',
    ];
}
