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
    const BUSINESS_SPECIALIST = 2;  // Chuyên viên kinh doanh
    const BUSINESS_LEADER = 3;      // Trưởng nhóm kinh doanh
    const BUSINESS_MANAGER = 4;     // Trưởng phòng kinh doanh
    const BUSINESS_DIRECTOR = 5;    // Giám đốc kinh doanh
    const CEO = 6;                  // Tổng giám đốc
}
