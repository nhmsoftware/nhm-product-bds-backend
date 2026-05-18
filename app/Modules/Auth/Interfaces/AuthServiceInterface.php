<?php

namespace App\Modules\Auth\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Auth\DTO\ForgotPasswordDTO;
use App\Modules\Auth\DTO\RegisterDTO;
use App\Modules\Auth\DTO\ResetPasswordDTO;
use App\Modules\Auth\DTO\VerifyOtpDTO;

interface AuthServiceInterface
{
    /**
     * Đăng ký tài khoản mới.
     * 
     * @param RegisterDTO $dto
     * @return ServiceReturn
     */
    public function register(RegisterDTO $dto): ServiceReturn;

    /**
     * Đăng nhập hệ thống.
     * 
     * @param \App\Modules\Auth\DTO\LoginDTO $dto
     * @return ServiceReturn
     */
    public function login(\App\Modules\Auth\DTO\LoginDTO $dto): ServiceReturn;

    /**
     * Yêu cầu quên mật khẩu (Gửi OTP).
     * 
     * @param ForgotPasswordDTO $dto
     * @return ServiceReturn
     */
    public function forgotPassword(ForgotPasswordDTO $dto): ServiceReturn;

    /**
     * Xác thực mã OTP.
     * 
     * @param VerifyOtpDTO $dto
     * @return ServiceReturn
     */
    public function verifyOtp(VerifyOtpDTO $dto): ServiceReturn;

    /**
     * Đặt lại mật khẩu mới.
     * 
     * @param ResetPasswordDTO $dto
     * @return ServiceReturn
     */
    public function resetPassword(ResetPasswordDTO $dto): ServiceReturn;

    /**
     * Đăng xuất hệ thống.
     * 
     * @return ServiceReturn
     */
    public function logout(): ServiceReturn;

    /**
     * Lấy thông tin hồ sơ cá nhân của người dùng (UC-030).
     * 
     * @param string $userId
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function getProfile(string $userId): ServiceReturn;

    /**
     * Cập nhật thông tin hồ sơ cá nhân của người dùng (UC-031).
     * 
     * @param \App\Modules\Auth\DTO\UpdateProfileDTO $dto
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function updateProfile(\App\Modules\Auth\DTO\UpdateProfileDTO $dto): ServiceReturn;

    /**
     * Thay đổi mật khẩu tài khoản của người dùng (UC-032).
     * 
     * @param \App\Modules\Auth\DTO\ChangePasswordDTO $dto
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function changePassword(\App\Modules\Auth\DTO\ChangePasswordDTO $dto): ServiceReturn;

    /**
     * Lấy thông tin hồ sơ nhân sự cá nhân (UC-033).
     * 
     * @param string $userId
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function getEmployeeProfile(string $userId): ServiceReturn;

    /**
     * Cập nhật thông tin hồ sơ nhân sự cá nhân (UC-034).
     * 
     * @param \App\Modules\Auth\DTO\UpdateEmployeeProfileDTO $dto
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function updateEmployeeProfile(\App\Modules\Auth\DTO\UpdateEmployeeProfileDTO $dto): ServiceReturn;

    /**
     * Tải lên tài liệu hồ sơ nhân sự (UC-035).
     * 
     * @param \App\Modules\Auth\DTO\UploadEmployeeDocumentDTO $dto
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function uploadEmployeeDocument(\App\Modules\Auth\DTO\UploadEmployeeDocumentDTO $dto): ServiceReturn;
}

