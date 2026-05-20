<?php

namespace App\Modules\SiteTour\Events;

use App\Modules\SiteTour\Models\SiteTour;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Sự kiện nhân viên check-in dẫn khách tham quan (Site Tour) thành công.
 */
final class SiteTourCheckedIn
{
    use Dispatchable, SerializesModels;

    /**
     * Khởi tạo sự kiện.
     *
     * @param SiteTour $siteTour Lượt dẫn khách được lưu
     */
    public function __construct(
        public readonly SiteTour $siteTour
    ) {
    }
}
