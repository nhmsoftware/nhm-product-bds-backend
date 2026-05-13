<?php

namespace App\Modules\Auth\Interfaces;

use App\Core\DTOs\ServiceReturn;
use App\Modules\Auth\DTO\RegisterDTO;
use App\Modules\Auth\DTO\ForgotPasswordDTO;
use App\Modules\Auth\DTO\VerifyOtpDTO;
use App\Modules\Auth\DTO\ResetPasswordDTO;

interface AuthServiceInterface
{
    /**
     * Đăng ký tài khoản mới
     */
    public function register(RegisterDTO $dto): ServiceReturn;

    /**
     * Đăng nhập hệ thống
     */
    public function login(\App\Modules\Auth\DTO\LoginDTO $dto): ServiceReturn;

    /**
     * Yêu cầu quên mật khẩu (Gửi OTP)
     */
    public function forgotPassword(ForgotPasswordDTO $dto): ServiceReturn;

    /**
     * Xác thực mã OTP
     */
    public function verifyOtp(VerifyOtpDTO $dto): ServiceReturn;

    /**
     * Đặt lại mật khẩu mới
     */
    public function resetPassword(ResetPasswordDTO $dto): ServiceReturn;

    /**
     * Đăng xuất hệ thống
     */
    public function logout(): ServiceReturn;
}
