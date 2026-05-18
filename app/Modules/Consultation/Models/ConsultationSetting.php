<?php

namespace App\Modules\Consultation\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ConsultationSetting',
    title: 'ConsultationSetting Model',
    description: 'Thông tin cấu hình liên hệ tư vấn hệ thống',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'd3b07384-d113-49c2-a558-e244247a88ca'),
        new OA\Property(property: 'hotline', type: 'string', example: '1900633633'),
        new OA\Property(property: 'email', type: 'string', nullable: true, example: 'contact@example.com'),
        new OA\Property(property: 'address', type: 'string', nullable: true, example: 'Tòa nhà Landmark 81, TP.HCM'),
        new OA\Property(property: 'is_callback_enabled', type: 'boolean', example: true),
        new OA\Property(property: 'is_message_form_enabled', type: 'boolean', example: true),
        new OA\Property(property: 'working_hours', type: 'string', nullable: true, example: 'Thứ 2 - Thứ 7: 8:00 - 18:00'),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class ConsultationSetting extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'consultation_settings';

    protected $fillable = [
        'hotline',
        'email',
        'address',
        'is_callback_enabled',
        'is_message_form_enabled',
        'working_hours',
        'is_active',
    ];

    protected $casts = [
        'id' => 'string',
        'is_callback_enabled' => 'boolean',
        'is_message_form_enabled' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
