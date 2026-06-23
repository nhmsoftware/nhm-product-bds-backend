<?php

namespace App\Modules\Auth\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Department extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'departments';

    protected $fillable = [
        'name',
        'code',
        'manager_id',
        'branch_id',
        'kpi_quota',
        'is_active',
    ];

    protected $casts = [
        'id' => 'string',
        'manager_id' => 'string',
        'branch_id' => 'string',
        'kpi_quota' => 'integer',
        'is_active' => 'boolean',
    ];

    public function manager(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Modules\Branch\Models\Branch::class, 'branch_id');
    }

    protected static function booted(): void
    {
        static::deleting(function (Department $dept) {
            $hasUsers = User::where('department_id', $dept->id)->exists();
            if ($hasUsers) {
                \Filament\Notifications\Notification::make()
                    ->title('Không thể xóa phòng ban')
                    ->body('Phòng ban này đang có nhân sự trực thuộc.')
                    ->danger()
                    ->send();
                return false;
            }
        });
    }
}
