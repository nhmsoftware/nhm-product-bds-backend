<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default GPS Coordinates
    |--------------------------------------------------------------------------
    |
    | Tọa độ GPS mặc định để kiểm tra phạm vi check-in khi dẫn khách.
    | Dùng khi dự án chưa có tọa độ riêng trong database.
    |
    */
    'default_lat' => (float) env('DEFAULT_PROJECT_LAT', 21.04039963677646),
    'default_lng' => (float) env('DEFAULT_PROJECT_LNG', 105.77333406318525),
];
