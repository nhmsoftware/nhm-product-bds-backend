<?php

declare(strict_types=1);

namespace App\Modules\Notification\Models;

use Illuminate\Notifications\DatabaseNotification;
use OpenApi\Attributes as OA;

/**
 * Class Notification
 *
 * Model thông báo của hệ thống, kế thừa từ DatabaseNotification của Laravel.
 * Hỗ trợ tất cả các tính năng có sẵn của Laravel Notifications.
 *
 * @property string $id               UUID của thông báo
 * @property string $type             Tên class notification đã gửi
 * @property string $notifiable_type  Loại model nhận thông báo (thường là User)
 * @property string $notifiable_id    UUID của đối tượng nhận thông báo
 * @property array  $data             Dữ liệu payload thông báo
 * @property \Illuminate\Support\Carbon|null $read_at Thời điểm đã đọc (null = chưa đọc)
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Notification',
    title: 'Notification Model',
    description: 'Thông tin thông báo của người dùng',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', description: 'ID duy nhất của thông báo'),
        new OA\Property(property: 'type', type: 'string', description: 'Loại thông báo (tên class)'),
        new OA\Property(
            property: 'data',
            type: 'object',
            description: 'Nội dung chi tiết của thông báo',
            properties: [
                new OA\Property(property: 'title', type: 'string', example: 'Đơn nghỉ phép đã được phê duyệt'),
                new OA\Property(property: 'body', type: 'string', example: 'Đơn xin nghỉ phép của bạn từ 20/05 đến 22/05 đã được duyệt.'),
                new OA\Property(property: 'action_type', type: 'string', nullable: true, example: 'leave_request'),
                new OA\Property(property: 'action_id', type: 'string', format: 'uuid', nullable: true, example: 'uuid-string'),
            ]
        ),
        new OA\Property(property: 'is_read', type: 'boolean', description: 'Trạng thái đã đọc', example: false),
        new OA\Property(property: 'read_at', type: 'string', format: 'date-time', nullable: true, description: 'Thời điểm đã đọc'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', description: 'Thời điểm gửi thông báo'),
    ]
)]
class Notification extends DatabaseNotification
{
    /**
     * Các trường được phép ghi khi tạo thông báo database.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
    ];

    /**
     * Kiểu dữ liệu cần ép khi đọc/ghi thông báo.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Notification $notification) {
            $groupId = data_get($notification->data, 'group_id');
            if ($groupId) {
                static::withoutEvents(function () use ($groupId) {
                    static::query()
                        ->whereRaw("data::json->>'group_id' = ?", [$groupId])
                        ->delete();
                });
            }
        });
    }

    /**
     * Ghi đè toArray để thêm trường is_read để Frontend dễ sử dụng
     * và chuẩn hóa cấu trúc response theo chuẩn dự án.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'          => (string) $this->id,
            'type'        => $this->type,
            'data'        => $this->data,
            'title'       => $this->data['title'] ?? null,
            'body'        => $this->data['body'] ?? null,
            'user_id'     => (string) $this->notifiable_id,
            'notifiable_id' => (string) $this->notifiable_id,
            'action_type' => $this->data['action_type'] ?? null,
            'action_id'   => $this->data['action_id'] ?? null,
            'is_read'     => $this->read_at !== null,
            'read_at'     => $this->read_at?->toIso8601String(),
            'created_at'  => $this->created_at->toIso8601String(),
        ];
    }
}
