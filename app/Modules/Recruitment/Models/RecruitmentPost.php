<?php

declare(strict_types=1);

namespace App\Modules\Recruitment\Models;

use App\Modules\Recruitment\Models\Enums\RecruitmentPostStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class RecruitmentPost
 *
 * @property string $id
 * @property string $title
 * @property string|null $image
 * @property string|null $branch_id
 * @property string $job_position
 * @property string $department
 * @property string|null $short_description
 * @property string|null $content
 * @property string|null $job_description
 * @property string|null $candidate_requirements
 * @property string|null $benefits
 * @property RecruitmentPostStatus $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @mixin \Eloquent
 */
class RecruitmentPost extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'recruitment_posts';

    protected $fillable = [
        'title',
        'image',
        'branch_id',
        'job_position',
        'department',
        'short_description',
        'content',
        'job_description',
        'candidate_requirements',
        'benefits',
        'status',
    ];

    protected $casts = [
        'status' => RecruitmentPostStatus::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ─── Relationships ───────────────────────────────────────────

    /**
     * Chi nhánh của bài tuyển dụng.
     */
    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Modules\Branch\Models\Branch::class, 'branch_id');
    }

    // ─── Helpers ─────────────────────────────────────────────────


    /**
     * Kiểm tra xem bài viết có đang hiển thị không.
     */
    public function isShowing(): bool
    {
        return $this->status === RecruitmentPostStatus::SHOWING;
    }

    /**
     * Ghi đè phương thức toArray để serialize giá trị status thành dạng chữ (lowercase).
     */
    public function toArray(): array
    {
        $array = parent::toArray();
        if (isset($array['status']) && $this->status instanceof RecruitmentPostStatus) {
            $array['status'] = strtolower($this->status->name);
        }
        return $array;
    }
}
