<?php

declare(strict_types=1);

namespace App\Modules\EmployeeReferral\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Auth\Models\User;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ReferralQrService extends BaseService
{
    /**
     * Tải hoặc tạo mã QR tuyển dụng cá nhân (UC-098)
     *
     * @param User $user Người dùng (Employee)
     * @return ServiceReturn
     */
    public function getRecruitmentQr(User $user): ServiceReturn
    {
        return $this->execute(function () use ($user) {
            $staffCode = $user->staff_code;
            $this->validate(!empty($staffCode), 'Bạn chưa có mã giới thiệu tuyển dụng.', 404);

            $fileName = "qrs/recruitment_{$staffCode}.svg";
            $disk = Storage::disk('public');

            if (!$disk->exists($fileName)) {
                // Đảm bảo thư mục tồn tại
                if (!$disk->exists('qrs')) {
                    $disk->makeDirectory('qrs');
                }

                $frontendUrl = config('app.frontend_url', 'https://bdsapp.vn');
                $recruitmentCode = 'REC-' . $staffCode;
                // Link chia sẻ để quét mã (ví dụ link tải app hoặc register kèm ref_code)
                $qrData = "{$frontendUrl}/register?ref={$recruitmentCode}&type=recruitment";

                try {
                    $qrSvg = QrCode::size(300)->generate($qrData);
                    $disk->put($fileName, $qrSvg);
                } catch (\Exception $e) {
                    $this->throw('Không thể tạo mã QR tuyển dụng: ' . $e->getMessage(), 500);
                }
            }

            if (!$disk->exists($fileName)) {
                $this->throw('Không thể tải mã QR tuyển dụng.', 500);
            }

            $qrUrl = $disk->url($fileName);
            $recruitmentCode = 'REC-' . $staffCode;

            return [
                'qr_url' => $qrUrl,
                'referral_code' => $recruitmentCode,
                'description' => 'Sử dụng mã này để giới thiệu nhân sự mới tham gia hệ thống.',
                'share_text' => "Hãy tham gia mạng lưới của chúng tôi trên BDS App! Mã giới thiệu của tôi: {$recruitmentCode}",
            ];
        }, false, 'ReferralQrService::getRecruitmentQr');
    }

    /**
     * Tải hoặc tạo mã QR giới thiệu khách hàng cá nhân (UC-100)
     *
     * @param User $user Người dùng (Employee)
     * @return ServiceReturn
     */
    public function getCustomerQr(User $user): ServiceReturn
    {
        return $this->execute(function () use ($user) {
            $staffCode = $user->staff_code;
            $this->validate(!empty($staffCode), 'Bạn chưa có mã giới thiệu khách hàng.', 404);

            $fileName = "qrs/customer_{$staffCode}.svg";
            $disk = Storage::disk('public');

            if (!$disk->exists($fileName)) {
                // Đảm bảo thư mục tồn tại
                if (!$disk->exists('qrs')) {
                    $disk->makeDirectory('qrs');
                }

                $frontendUrl = config('app.frontend_url', 'https://bdsapp.vn');
                $customerCode = 'CUS-' . $staffCode;
                // Link chia sẻ để quét mã (ví dụ link tải app hoặc đăng ký khách hàng kèm ref_code)
                $qrData = "{$frontendUrl}/register?ref={$customerCode}&type=customer";

                try {
                    $qrSvg = QrCode::size(300)->generate($qrData);
                    $disk->put($fileName, $qrSvg);
                } catch (\Exception $e) {
                    $this->throw('Không thể tạo mã QR khách hàng: ' . $e->getMessage(), 500);
                }
            }

            if (!$disk->exists($fileName)) {
                $this->throw('Không thể tải mã QR khách hàng.', 500);
            }

            $qrUrl = $disk->url($fileName);
            $customerCode = 'CUS-' . $staffCode;

            return [
                'qr_url' => $qrUrl,
                'referral_code' => $customerCode,
                'description' => 'Sử dụng mã này để giới thiệu khách hàng tham gia hệ thống.',
                'share_text' => "Tìm hiểu các dự án hấp dẫn tại BDS App! Mã giới thiệu khách hàng của tôi: {$customerCode}",
            ];
        }, false, 'ReferralQrService::getCustomerQr');
    }
}
