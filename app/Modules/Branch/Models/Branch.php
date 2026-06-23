<?php

namespace App\Modules\Branch\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'code',
        'area',
        'is_active',
        'sort',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort' => 'integer',
    ];
    protected static function booted(): void
    {
        static::deleting(function (Branch $branch) {
            $hasUsers = \App\Modules\Auth\Models\User::where('branch_id', $branch->id)->exists();
            if ($hasUsers) {
                \Filament\Notifications\Notification::make()
                    ->title('Không thể xóa chi nhánh')
                    ->body('Chi nhánh này đang có nhân sự trực thuộc.')
                    ->danger()
                    ->send();
                return false;
            }

            $hasAreas = \App\Modules\Area\Models\Area::where('branch_id', $branch->id)->exists();
            if ($hasAreas) {
                \Filament\Notifications\Notification::make()
                    ->title('Không thể xóa chi nhánh')
                    ->body('Chi nhánh này đang chứa các khu đất.')
                    ->danger()
                    ->send();
                return false;
            }
        });
    }
}
