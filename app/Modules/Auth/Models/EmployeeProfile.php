<?php

declare(strict_types=1);

namespace App\Modules\Auth\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OpenApi\Attributes as OA;

/**
 * Class EmployeeProfile
 *
 * @property string $id
 * @property string $user_id
 * @property string $employee_title
 * @property string $identity_card
 * @property \Illuminate\Support\Carbon|null $dob
 * @property string $bank_account_name
 * @property string $bank_account_number
 * @property string $bank_name
 * @property string $education
 * @property string $major
 * @property string $experience
 * @property array|null $attachments
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User|null $user
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'EmployeeProfile',
    title: 'Employee Profile Model',
    description: 'Bảng thông tin chi tiết hồ sơ nhân sự của hệ thống.',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '123e4567-e89b-12d3-a456-426614174000'),
        new OA\Property(property: 'user_id', type: 'string', format: 'uuid', example: '123e4567-e89b-12d3-a456-426614174000'),
        new OA\Property(property: 'employee_title', type: 'string', nullable: true, example: 'Nhân viên xuất sắc năm 2026'),
        new OA\Property(property: 'identity_card', type: 'string', nullable: true, example: '037123456789'),
        new OA\Property(property: 'dob', type: 'string', format: 'date', nullable: true, example: '1995-10-15'),
        new OA\Property(property: 'bank_account_name', type: 'string', nullable: true, example: 'NGUYEN VAN A'),
        new OA\Property(property: 'bank_account_number', type: 'string', nullable: true, example: '190345678910'),
        new OA\Property(property: 'bank_name', type: 'string', nullable: true, example: 'Techcombank'),
        new OA\Property(property: 'education', type: 'string', nullable: true, example: 'Đại học Bách Khoa TP.HCM'),
        new OA\Property(property: 'major', type: 'string', nullable: true, example: 'Công nghệ thông tin'),
        new OA\Property(property: 'experience', type: 'string', nullable: true, example: '3 năm kinh nghiệm lập trình Laravel'),
        new OA\Property(
            property: 'attachments',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'type', type: 'string', example: 'labor_contract'),
                    new OA\Property(property: 'name', type: 'string', example: 'Hop_Dong_Lao_Dong.pdf'),
                    new OA\Property(property: 'url', type: 'string', example: 'https://bds-app.s3.amazonaws.com/contracts/hopdong.pdf'),
                ],
                type: 'object'
            ),
            nullable: true
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-05-18T16:10:00Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-05-18T16:10:00Z'),
    ]
)]
class EmployeeProfile extends Model
{
    use HasUuids;

    protected $table = 'employee_profiles';

    protected $fillable = [
        'user_id',
        'employee_title',
        'identity_card',
        'dob',
        'bank_account_name',
        'bank_account_number',
        'bank_name',
        'education',
        'major',
        'experience',
        'attachments',
        'reward_points',
    ];

    protected $casts = [
        'dob'         => 'date',
        'attachments' => 'array',
    ];

    // ─── Relationships ───────────────────────────────────────────

    /**
     * Quan hệ tới User sở hữu hồ sơ nhân sự này.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Sinh câu lệnh SQL SELECT RAW tính toán điểm KPI động của nhân viên.
     *
     * @param string $userIdColumn Tên cột user id dùng để so khớp (mặc định: 'employee_profiles.user_id')
     * @return string
     */
    public static function getKpiPointsSelectRaw(string $userIdColumn = 'employee_profiles.user_id'): string
    {
        $settings = \App\Modules\Area\Models\InventorySetting::pluck('value', 'key');
        $successfulTransactionPoints = (float) data_get($settings->get('kpi_points_successful_transaction'), 'points', 10);
        $siteTourPoints = (float) data_get($settings->get('kpi_points_site_tour'), 'points', 1);
        $customerMeetingPoints = (float) data_get($settings->get('kpi_points_customer_meeting'), 'points', 0.5);
        $successfulReferralPoints = (float) data_get($settings->get('kpi_points_successful_referral'), 'points', 1);
        $workDayPoints = (float) data_get($settings->get('kpi_points_work_day_rate'), 'points', 1);
        $workDaysStep = (int) data_get($settings->get('kpi_points_work_day_rate'), 'days', 5);
        $absencePenalty = (float) data_get($settings->get('kpi_points_absence_penalty'), 'points', 0.5);

        return "(
            (SELECT COALESCE(COUNT(*), 0) FROM lot_deposit_requests WHERE lot_deposit_requests.user_id = {$userIdColumn} AND lot_deposit_requests.status IN (2, 4)) * {$successfulTransactionPoints}
            + (SELECT COALESCE(COUNT(*), 0) FROM site_tours WHERE site_tours.user_id = {$userIdColumn}) * {$siteTourPoints}
            + (SELECT COALESCE(COUNT(*), 0) FROM customer_meetings WHERE customer_meetings.user_id = {$userIdColumn}) * {$customerMeetingPoints}
            + (SELECT COALESCE(COUNT(*), 0) FROM referral_histories WHERE referral_histories.referrer_id = {$userIdColumn} AND referral_histories.referral_type = 1 AND referral_histories.status = 2) * {$successfulReferralPoints}
            + (CASE WHEN {$workDaysStep} > 0 THEN FLOOR((SELECT COALESCE(COUNT(*), 0) FROM attendances WHERE attendances.user_id = {$userIdColumn} AND attendances.status IN (1, 2)) / {$workDaysStep}) * {$workDayPoints} ELSE 0 END)
            - (SELECT COALESCE(COUNT(*), 0) FROM attendances WHERE attendances.user_id = {$userIdColumn} AND attendances.status = 3) * {$absencePenalty}
        )";
    }
}
