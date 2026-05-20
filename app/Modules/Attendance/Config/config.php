<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cấu hình chấm công văn phòng (UC-036)
    |--------------------------------------------------------------------------
    |
    | Cấu hình tọa độ GPS, bán kính cho phép, SSID Wifi văn phòng và giờ làm việc.
    |
    */

    'office_latitude' => env('ATTENDANCE_OFFICE_LATITUDE', 10.7769), // Tọa độ văn phòng mặc định (Quận 1, TP. HCM)
    'office_longitude' => env('ATTENDANCE_OFFICE_LONGITUDE', 106.7009),
    'office_radius_meters' => env('ATTENDANCE_OFFICE_RADIUS_METERS', 100), // Bán kính cho phép check-in qua GPS (mét)
    'office_wifi_ssid' => env('ATTENDANCE_OFFICE_WIFI_SSID', 'BDS_Office_Wifi'), // Tên Wifi văn phòng hợp lệ
    'shift_start_time' => env('ATTENDANCE_SHIFT_START_TIME', '08:30:00'), // Giờ bắt đầu ca (Sau giờ này sẽ bị tính đi trễ - 'late')
];
