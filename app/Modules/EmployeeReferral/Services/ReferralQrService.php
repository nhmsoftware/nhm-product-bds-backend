<?php

declare(strict_types=1);

namespace App\Modules\EmployeeReferral\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Auth\Models\User;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

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

            $recruitmentCode = 'REC-' . $staffCode;
            $qrValue = $this->buildAppDownloadUrl($recruitmentCode, 'recruitment');
            $qrUrl = $this->generateQrUrl($qrValue, 'recruitment', $staffCode);

            return [
                'qr_value' => $qrValue,
                'qr_url' => $qrUrl,
                'referral_code' => $recruitmentCode,
                'referral_type' => 'recruitment',
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

            $customerCode = 'CUS-' . $staffCode;
            $qrValue = $this->buildAppDownloadUrl($customerCode, 'customer');
            $qrUrl = $this->generateQrUrl($qrValue, 'customer', $staffCode);

            return [
                'qr_value' => $qrValue,
                'qr_url' => $qrUrl,
                'referral_code' => $customerCode,
                'referral_type' => 'customer',
                'description' => 'Sử dụng mã này để giới thiệu khách hàng tham gia hệ thống.',
                'share_text' => "Tìm hiểu các dự án hấp dẫn tại BDS App! Mã giới thiệu khách hàng của tôi: {$customerCode}",
            ];
        }, false, 'ReferralQrService::getCustomerQr');
    }


    private function buildAppDownloadUrl(string $referralCode, string $type): string
    {
        return rtrim((string) config('app.url'), '/') . '/api/v1/referrals/open?' . http_build_query([
            'ref' => $referralCode,
            'type' => $type,
        ]);
    }

    private function generateQrUrl(string $qrValue, string $type, string $staffCode): string
    {
        $fileName = sprintf('%s_%s.svg', $type, preg_replace('/[^A-Za-z0-9_-]/', '-', $staffCode));
        $directory = public_path('storage/qrs');
        $filePath = $directory . DIRECTORY_SEPARATOR . $fileName;

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $options = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64' => false,
            'scale' => 9,
            'quietzoneSize' => 4,
            'svgAddXmlHeader' => true,
        ]);

        (new QRCode($options))->render($qrValue, $filePath);

        return rtrim((string) config('app.url'), '/') . '/storage/qrs/' . $fileName;
    }
}
