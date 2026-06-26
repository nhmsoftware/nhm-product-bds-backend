<?php

declare(strict_types=1);

namespace App\Modules\Area\Models;

use App\Modules\Area\Models\Enums\AreaStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use OpenApi\Attributes as OA;

/**
 * Class Area
 *
 * @property string $id
 * @property string $name
 * @property string|null $sales_board_image
 * @property string|null $sales_board_iframe
 * @property string|null $planning_check_url
 * @property array|null $sales_board_images
 * @property int $total_lots
 * @property int $remaining_lots
 * @property float|null $area_size
 * @property string|null $direction
 * @property AreaStatus|null $status
 * @property string|null $label_status
 * @property string|null $legal_text
 * @property bool $is_featured
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Area',
    title: 'Area Model',
    description: 'Thông tin khu đất / bảng hàng',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b1234567'),
        new OA\Property(property: 'name', type: 'string', example: 'Phân khu A - Golden Land'),
        new OA\Property(property: 'sales_board_image', type: 'string', nullable: true, example: 'https://example.com/images/sales_board.jpg'),
        new OA\Property(property: 'sales_board_iframe', type: 'string', nullable: true, example: 'https://quyhoach24h.vn?ref=C5WA63ND'),
        new OA\Property(property: 'planning_check_url', type: 'string', nullable: true, example: 'https://quyhoach24h.vn?ref=C5WA63ND'),
        new OA\Property(property: 'sales_board_images', type: 'array', items: new OA\Items(type: 'string'), nullable: true, example: ['https://example.com/images/sales_board_1.jpg']),
        new OA\Property(property: 'area_size', type: 'number', format: 'float', nullable: true, example: 120.5),
        new OA\Property(property: 'direction', type: 'string', nullable: true, example: 'Đông Nam'),
        new OA\Property(property: 'status', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'label_status', type: 'string', nullable: true, example: 'Đang mở bán'),
        new OA\Property(property: 'legal_text', type: 'string', nullable: true, example: 'Quyết định 1/500, Sổ hồng từng phân khu'),
        new OA\Property(property: 'total_lots', type: 'integer', example: 100),
        new OA\Property(property: 'remaining_lots', type: 'integer', example: 45),
        new OA\Property(property: 'is_featured', type: 'boolean', example: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class Area extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'areas';

    protected $fillable = [
        'name',
        'keywords',
        'location',
        'image',
        'banner',
        'sales_board_image',
        'sales_board_iframe',
        'planning_check_url',
        'sales_board_images',
        'total_lots',
        'remaining_lots',
        'area_size',
        'direction',
        'status',
        'type',
        'is_public',
        'description',
        'amenities',
        'floor_plans',
        'legal_info',
        'legal_text',
        'brochure',
        'contact_info',
        'google_maps_url',
        'location_image',
        'planning_info',
        'branch_id',
        'area_type_id',
        'is_featured',
        'is_locked',
    ];

    protected $casts = [
        'id' => 'string',
        'total_lots' => 'integer',
        'remaining_lots' => 'integer',
        'area_size' => 'float',
        'status' => AreaStatus::class,
        'is_public' => 'boolean',
        'is_featured' => 'boolean',
        'is_locked' => 'boolean',
        'keywords' => 'array',
        'banner' => 'array',
        'amenities' => 'array',
        'floor_plans' => 'array',
        'legal_info' => 'array',
        'contact_info' => 'array',
        'planning_info' => 'array',
        'sales_board_images' => 'array',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Area $area) {
            // 1. Kiểm tra xem có lô đất nào trong khu đang bị giữ chỗ (cọc) hoặc đã bán không
            $hasLockedOrSoldLots = $area->lots()
                ->where(function ($q) {
                    $q->whereIn('status', [
                        \App\Modules\Area\Models\Enums\LotStatus::SOLD->value,
                        \App\Modules\Area\Models\Enums\LotStatus::RESERVED->value,
                    ])->orWhere('is_locked', true);
                })
                ->exists();

            if ($hasLockedOrSoldLots) {
                \Filament\Notifications\Notification::make()
                    ->title('Không thể xóa khu đất')
                    ->body('Khu đất này có chứa lô đất đang giữ chỗ hoặc đã bán.')
                    ->danger()
                    ->send();

                return false;
            }

            // 2. Cascade soft delete các lô đất liên quan
            $area->lots()->delete();

            // 3. Cascade soft delete các phân quyền liên quan (area_assignments)
            \DB::table('area_assignments')->where('area_id', $area->id)->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);

            // 4. Cascade soft delete các bình luận liên quan (area_comments)
            $area->comments()->delete();
        });
    }

    /**
     * Accessor: trả về nhãn tiếng Việt của trạng thái khu đất.
     *
     * @return string|null
     */
    public function getLabelStatusAttribute(): ?string
    {
        if ($this->status instanceof AreaStatus) {
            return $this->status->label();
        }
        if ($this->status !== null) {
            $enum = AreaStatus::tryFrom((int) $this->status);
            return $enum?->label();
        }
        return null;
    }

    /**
     * Mutator: chấp nhận cả int lẫn AreaStatus instance.
     */
    public function setStatusAttribute(mixed $value): void
    {
        if ($value === null) {
            $this->attributes['status'] = null;
            return;
        }
        $this->attributes['status'] = $value instanceof AreaStatus
            ? $value->value
            : (int) $value;
    }

    /**
     * Accessor: Lấy giá nhỏ nhất của các lô đất trong khu đất này.
     *
     * @return int|null
     */
    public function getPriceAttribute(): ?int
    {
        if ($this->relationLoaded('lots')) {
            return $this->lots->min('price');
        }
        return $this->lots()->min('price');
    }

    /**
     * Accessor: Lấy đơn giá nhỏ nhất của các lô đất trong khu đất này.
     *
     * @return int|null
     */
    public function getUnitPriceAttribute(): ?int
    {
        if ($this->relationLoaded('lots')) {
            return $this->lots->min('unit_price');
        }
        return $this->lots()->min('unit_price');
    }

    /**
     * Override toArray để luôn include label_status và branch name trong response.
     */
    public function toArray(): array
    {
        $array = parent::toArray();
        $array['label_status'] = $this->label_status;
        $array['branch'] = $this->branch;
        $array['price'] = $this->price;
        $array['unit_price'] = $this->unit_price;
        $array['type'] = $this->type;
        $array['area_type_id'] = $this->area_type_id;
        return $array;
    }



    /**
     * Loại hình khu đất.
     */
    public function areaType(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AreaType::class, 'area_type_id');
    }

    /**
     * Accessor trả về tên loại hình khu đất.
     */
    public function getTypeAttribute(): ?string
    {
        $areaTypeModel = $this->getRelationValue('areaType');
        if (!$areaTypeModel && $this->area_type_id) {
            $areaTypeModel = $this->areaType()->first();
            if ($areaTypeModel) {
                $this->setRelation('areaType', $areaTypeModel);
            }
        }
        return $areaTypeModel?->name ?? $this->attributes['type'] ?? null;
    }

    /**
     * Chi nhánh quản lý khu đất này.
     */
    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Modules\Branch\Models\Branch::class, 'branch_id');
    }

    /**
     * Accessor trả về tên chi nhánh để tương thích với các API cũ.
     */
    public function getBranchAttribute(): ?string
    {
        $branchModel = $this->getRelationValue('branch');
        if (!$branchModel && $this->branch_id) {
            $branchModel = $this->branch()->first();
            if ($branchModel) {
                $this->setRelation('branch', $branchModel);
            }
        }
        return $branchModel?->name;
    }


    /**
     * Tất cả bản ghi phân quyền role-level (vai trò) thuộc khu đất này.
     */
    public function roleAssignments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AreaAssignment::class, 'area_id')
            ->where('assignable_type', 'role');
    }

    /**
     * Danh sách người dùng được cấp quyền truy cập khu đất này.
     */
    public function assignedUsers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany

    {
        return $this->belongsToMany(
            \App\Modules\Auth\Models\User::class,
            'area_assignments',
            'area_id',
            'user_id'
        )->withTimestamps()->whereNull('area_assignments.deleted_at');
    }

    /**
     * Danh sách lô đất thuộc khu đất này.
     */
    public function lots(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Lot::class, 'area_id');
    }

    /**
     * Danh sách bình luận của khu đất.
     */
    public function comments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AreaComment::class, 'area_id');
    }
}

