<?php

declare(strict_types=1);

namespace App\Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use OpenApi\Attributes as OA;
use App\Modules\Attendance\Models\Enums\AttendanceStatus;/**
 * Class Attendance
 *
 * @property string $id
 * @property string $user_id
 * @property \Illuminate\Support\Carbon|null $work_date
 * @property \Illuminate\Support\Carbon|null $check_in_at
 * @property float $check_in_lat
 * @property float $check_in_lng
 * @property string $check_in_method
 * @property string $check_in_wifi_ssid
 * @property string $check_in_device_name
 * @property \Illuminate\Support\Carbon|null $check_out_at
 * @property float $check_out_lat
 * @property float $check_out_lng
 * @property string $check_out_method
 * @property string $check_out_wifi_ssid
 * @property string $check_out_device_name
 * @property AttendanceStatus $status
 * @property string $note
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Attendance',
    title: 'Attendance Model',
    description: 'Thông tin chấm công hàng ngày của nhân viên',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b1234567'),
        new OA\Property(property: 'user_id', type: 'string', format: 'uuid', example: 'a1b2c3d4-e5f6-7a8b-9c0d-1e2f3a4b5c6d'),
        new OA\Property(property: 'work_date', type: 'string', format: 'date', example: '2026-05-19'),
        new OA\Property(property: 'check_in_at', type: 'string', format: 'date-time', nullable: true, example: '2026-05-19T08:15:23+07:00'),
        new OA\Property(property: 'check_in_lat', type: 'number', format: 'float', nullable: true, example: 10.7769),
        new OA\Property(property: 'check_in_lng', type: 'number', format: 'float', nullable: true, example: 106.7009),
        new OA\Property(property: 'check_in_method', type: 'string', enum: ['gps', 'wifi', 'qr'], nullable: true, example: 'gps'),
        new OA\Property(property: 'check_in_wifi_ssid', type: 'string', nullable: true, example: 'BDS_Office_Wifi'),
        new OA\Property(property: 'check_in_device_name', type: 'string', nullable: true, example: 'iPhone 15 Pro'),
        new OA\Property(property: 'check_out_at', type: 'string', format: 'date-time', nullable: true, example: null),
        new OA\Property(property: 'check_out_lat', type: 'number', format: 'float', nullable: true, example: null),
        new OA\Property(property: 'check_out_lng', type: 'number', format: 'float', nullable: true, example: null),
        new OA\Property(property: 'check_out_method', type: 'string', enum: ['gps', 'wifi', 'qr'], nullable: true, example: null),
        new OA\Property(property: 'check_out_wifi_ssid', type: 'string', nullable: true, example: null),
        new OA\Property(property: 'check_out_device_name', type: 'string', nullable: true, example: null),
        new OA\Property(property: 'status', type: 'string', enum: ['present', 'late', 'absent', 'half_day'], example: 'present'),
        new OA\Property(property: 'note', type: 'string', nullable: true, example: 'Đi làm đúng giờ'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-05-19T08:15:23+07:00'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-05-19T08:15:23+07:00'),
    ]
)]
class Attendance extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'attendances';

    protected $fillable = [
        'user_id',
        'work_date',
        'check_in_at',
        'check_in_lat',
        'check_in_lng',
        'check_in_method',
        'check_in_wifi_ssid',
        'check_in_device_name',
        'check_out_at',
        'check_out_lat',
        'check_out_lng',
        'check_out_method',
        'check_out_wifi_ssid',
        'check_out_device_name',
        'status',
        'note',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'work_date' => 'date:Y-m-d',
        'check_in_at' => 'datetime',
        'check_in_lat' => 'float',
        'check_in_lng' => 'float',
        'check_out_at' => 'datetime',
        'check_out_lat' => 'float',
        'check_out_lng' => 'float',
        'status' => AttendanceStatus::class,
    ];

    public function setStatusAttribute($value)
    {
        if ($value === null) {
            $this->attributes['status'] = null;
            return;
        }
        $this->attributes['status'] = $value instanceof AttendanceStatus ? $value->value : AttendanceStatus::deserialize($value)->value;
    }

    public function toArray()
    {
        $array = parent::toArray();
        if (isset($array['status']) && $this->status instanceof AttendanceStatus) {
            $array['status'] = $this->status->serialize();
        }
        return $array;
    }
}
