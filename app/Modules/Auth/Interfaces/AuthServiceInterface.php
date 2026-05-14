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
}
