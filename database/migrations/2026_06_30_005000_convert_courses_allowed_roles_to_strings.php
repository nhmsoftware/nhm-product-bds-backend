<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Modules\Auth\Models\Enums\UserRole;

return new class extends Migration
{
    private const ROLE_MAP = [
        1 => 'employee',
        2 => 'tp_kd',
        3 => 'gdkd',
        4 => 'ceo',
        5 => 'super_admin',
        6 => 'buyer',
    ];

    public function up(): void
    {
        $courses = DB::table('courses')->whereNotNull('allowed_roles')->get();

        foreach ($courses as $course) {
            $allowedRoles = json_decode($course->allowed_roles, true);
            if (!is_array($allowedRoles)) {
                continue;
            }

            $newRoles = [];
            foreach ($allowedRoles as $role) {
                if (is_numeric($role)) {
                    $val = (int)$role;
                    if (isset(self::ROLE_MAP[$val])) {
                        $newRoles[] = self::ROLE_MAP[$val];
                    }
                } else {
                    $newRoles[] = $role; // Already string
                }
            }

            DB::table('courses')
                ->where('id', $course->id)
                ->update(['allowed_roles' => json_encode(array_values(array_unique($newRoles)))]);
        }
    }

    public function down(): void
    {
        $courses = DB::table('courses')->whereNotNull('allowed_roles')->get();
        $flipMap = array_flip(self::ROLE_MAP);

        foreach ($courses as $course) {
            $allowedRoles = json_decode($course->allowed_roles, true);
            if (!is_array($allowedRoles)) {
                continue;
            }

            $oldRoles = [];
            foreach ($allowedRoles as $role) {
                if (!is_numeric($role)) {
                    if (isset($flipMap[$role])) {
                        $oldRoles[] = $flipMap[$role];
                    }
                } else {
                    $oldRoles[] = (int)$role;
                }
            }

            DB::table('courses')
                ->where('id', $course->id)
                ->update(['allowed_roles' => json_encode(array_values(array_unique($oldRoles)))]);
        }
    }
};
