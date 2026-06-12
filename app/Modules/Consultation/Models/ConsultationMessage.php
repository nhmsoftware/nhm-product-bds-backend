<?php

declare(strict_types=1);

namespace App\Modules\Consultation\Models;

use App\Modules\Consultation\Models\Enums\ConsultationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use OpenApi\Attributes as OA;

/**
 * Class ConsultationMessage
 *
 * @property string $id
 * @property string $full_name
 * @property string $phone
 * @property string $email
 * @property string $project_id
 * @property string $project_name
 * @property string $content
 * @property string $request_type
 * @property string|null $preferred_callback_time
 * @property ConsultationStatus $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'ConsultationMessage',
    title: 'ConsultationMessage Model',
    description: 'Yêu cầu liên hệ tư vấn bất động sản của khách hàng',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'd3b07384-d113-49c2-a558-e244247a88ca'),
        new OA\Property(property: 'full_name', type: 'string', example: 'Nguyễn Văn A'),
        new OA\Property(property: 'phone', type: 'string', example: '0912345678'),
        new OA\Property(property: 'email', type: 'string', nullable: true, example: 'customer@example.com'),
        new OA\Property(property: 'project_id', type: 'string', format: 'uuid', nullable: true, example: 'd3b07384-d113-49c2-a558-e244247a88ca'),
        new OA\Property(property: 'project_name', type: 'string', nullable: true, example: 'Quy hoạch khu đô thị Thủ Thiêm'),
        new OA\Property(property: 'content', type: 'string', nullable: true, example: 'Tôi cần tư vấn thông tin chi tiết và chính sách bán hàng của dự án này.'),
        new OA\Property(property: 'request_type', type: 'string', example: 'consultation'),
        new OA\Property(property: 'preferred_callback_time', type: 'string', nullable: true, example: 'Thứ 7 tuần này, 09:00'),
        new OA\Property(property: 'status', type: 'integer', example: \App\Modules\Consultation\Models\Enums\ConsultationStatus::PENDING->value),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class ConsultationMessage extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'consultation_messages';

    protected $fillable = [
        'full_name',
        'phone',
        'email',
        'project_id',
        'project_name',
        'content',
        'request_type',
        'preferred_callback_time',
        'status',
    ];

    protected $casts = [
        'id' => 'string',
        'project_id' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'status' => ConsultationStatus::class,
    ];

    public function toArray()
    {
        $array = parent::toArray();
        if (isset($array['status']) && $this->status instanceof ConsultationStatus) {
            $array['status'] = $this->status->serialize();
        }
        return $array;
    }
}
