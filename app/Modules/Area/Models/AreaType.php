<?php

declare(strict_types=1);

namespace App\Modules\Area\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AreaType extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'area_types';

    protected $fillable = [
        'name',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function booted(): void
    {
        static::deleting(function (AreaType $areaType) {
            $hasAreas = $areaType->areas()->exists();

            if ($hasAreas) {
                \Filament\Notifications\Notification::make()
                    ->title('Không thể xóa loại hình')
                    ->body('Loại hình này đang được sử dụng bởi một hoặc nhiều khu đất.')
                    ->danger()
                    ->send();

                return false;
            }
        });
    }

    public function areas(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Area::class, 'area_type_id');
    }
}
