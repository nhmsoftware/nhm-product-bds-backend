<?php

namespace App\Modules\Auth\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobPosition extends Model
{
    use HasFactory;

    protected $table = 'job_positions';

    protected $fillable = [
        'name',
        'code',
    ];

    protected $casts = [
        'id' => 'integer',
    ];

    // Các hằng số ID định nghĩa cho Chức danh (job_position_id)
    const COLLABORATOR = 1;         // Cộng tác viên
    const BUSINESS_LEADER = 2;      // Trưởng nhóm kinh doanh
    const BUSINESS_MANAGER = 3;     // Trưởng phòng kinh doanh
    const BUSINESS_DIRECTOR = 4;    // Giám đốc kinh doanh
    const AREA_DIRECTOR = 5;        // Giám đốc khu vực
    const CEO = 6;                  // Tổng giám đốc
    const SUPER_ADMIN = 7;          // Quản trị hệ thống
    const CUSTOMER = 8;             // Khách hàng
    const BUSINESS_SPECIALIST = 9;  // Chuyên viên kinh doanh
}
