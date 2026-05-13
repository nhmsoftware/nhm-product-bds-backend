<?php

namespace App\Modules\Auth\Services;

use App\Core\DTOs\ServiceReturn;
use App\Core\Services\BaseService;
use App\Modules\Auth\DTO\RegisterDTO;
use App\Modules\Auth\DTO\ForgotPasswordDTO;
use App\Modules\Auth\DTO\VerifyOtpDTO;
use App\Modules\Auth\DTO\ResetPasswordDTO;
use App\Modules\Auth\Events\UserRegistered;
use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use App\Modules\Auth\Interfaces\AuthServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class AuthService extends BaseService implements AuthServiceInterface
{
    public function __construct(
        private readonly AuthRepositoryInterface $authRepository
    ) {
    }

    /**
     * @throws \Throwable
     */
    public function register(RegisterDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // 1. Kiểm tra email/phone tồn tại (phòng hờ race condition dù đã có unique validation)
            $this->validate(
                !$this->authRepository->findByEmail($dto->email),
                'Email đã được đăng ký.',
                422
            );

            $this->validate(
                !$this->authRepository->findByPhone($dto->phone),
                'Số điện thoại đã được đăng ký.',
                422
            );

            // 2. Chuẩn bị dữ liệu
            $data = $dto->toArray();
            $data['password']   = Hash::make($dto->password);
            $data['staff_code'] = $this->generateStaffCode();
            $data['role']       = 'buyer'; // Mặc định là buyer khi đăng ký qua public form
            $data['is_active']  = true;

            // 3. Tạo user
            $user = $this->authRepository->create($data);

            // 4. Bắn Event
            event(new UserRegistered($user));

            return $this->success($user, 'Đăng ký thành công.');
        }, useTransaction: true);
    }

    public function login(\App\Modules\Auth\DTO\LoginDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // 1. Xác định là email hay phone
            $field = filter_var($dto->username, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

            // 2. Tìm user
            $user = ($field === 'email')
                ? $this->authRepository->findByEmail($dto->username)
                : $this->authRepository->findByPhone($dto->username);

            $this->validate($user !== null, 'Tài khoản không tồn tại.', 404);

            // 3. Kiểm tra mật khẩu
            $this->validate(
                Hash::check($dto->password, $user->password),
                'Thông tin đăng nhập không chính xác.',
                401
            );

            // 4. Kiểm tra tài khoản bị khóa
            $this->validate(
                (bool) $user->is_active,
                'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.',
                403
            );

            // 5. Tạo token (Sử dụng JWT)
            $token = auth('api')->login($user);
            
            $this->validate($token !== false, 'Không thể tạo phiên đăng nhập.', 500);

            return $this->success([
                'access_token' => $token,
                'token_type'   => 'bearer',
                'expires_in'   => auth('api')->factory()->getTTL() * 60,
                'user'         => $user
            ], 'Đăng nhập thành công.');
        });
    }

    public function forgotPassword(ForgotPasswordDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // 1. Tìm user
            $user = $this->findUserByUsername($dto->username);
            $this->validate($user !== null, 'Tài khoản không tồn tại.', 404);
            $this->validate((bool) $user->is_active, 'Tài khoản của bạn đã bị khóa.', 403);

            // 2. Kiểm tra cooldown (60s)
            $cooldownKey = "otp_cooldown:{$dto->username}";
            $this->validate(!Cache::has($cooldownKey), 'Vui lòng đợi 60 giây trước khi yêu cầu mã mới.', 429);

            // 3. Tạo OTP (6 chữ số)
            $otp = (string) rand(100000, 999999);
            
            // 4. Lưu Cache (hết hạn sau 5 phút)
            Cache::put("otp:{$dto->username}", $otp, now()->addMinutes(5));
            Cache::put($cooldownKey, true, now()->addSeconds(60));
            Cache::put("otp_attempts:{$dto->username}", 0, now()->addMinutes(5));

            // 5. Gửi OTP (Giả lập qua log hoặc gọi service gửi SMS/Email)
            // \Log::info("OTP for {$dto->username}: {$otp}");
            
            return $this->success(null, 'Mã OTP đã được gửi.');
        });
    }

    public function verifyOtp(VerifyOtpDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $otpKey = "otp:{$dto->username}";
            $attemptKey = "otp_attempts:{$dto->username}";

            $this->validate(Cache::has($otpKey), 'Mã OTP đã hết hạn.', 400);

            $storedOtp = Cache::get($otpKey);
            $attempts = (int) Cache::get($attemptKey, 0);

            if ($storedOtp !== $dto->otp) {
                $attempts++;
                Cache::put($attemptKey, $attempts, now()->addMinutes(5));
                
                $this->validate($attempts < 5, 'Bạn đã nhập sai OTP quá 5 lần. Vui lòng gửi lại OTP mới.', 400);
                $this->throw('Mã OTP không hợp lệ.', 400);
            }

            return $this->success(null, 'Xác thực OTP thành công.');
        });
    }

    public function resetPassword(ResetPasswordDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // 1. Xác thực OTP lần cuối
            $otpKey = "otp:{$dto->username}";
            $this->validate(Cache::get($otpKey) === $dto->otp, 'Mã OTP không hợp lệ hoặc đã hết hạn.', 400);

            // 2. Tìm user
            $user = $this->findUserByUsername($dto->username);
            $this->validate($user !== null, 'Tài khoản không tồn tại.', 404);

            // 3. Kiểm tra mật khẩu cũ
            $this->validate(
                !Hash::check($dto->password, $user->password),
                'Mật khẩu mới không được trùng với mật khẩu cũ.',
                400
            );

            // 4. Cập nhật mật khẩu
            $this->authRepository->updateById($user->id, [
                'password' => Hash::make($dto->password)
            ]);

            // 5. Xóa OTP sau khi dùng xong
            Cache::forget($otpKey);
            Cache::forget("otp_attempts:{$dto->username}");

            return $this->success(null, 'Đổi mật khẩu thành công.');
        }, useTransaction: true);
    }

    public function logout(): ServiceReturn
    {
        return $this->execute(function () {
            auth('api')->logout();
            return $this->success(null, 'Đăng xuất thành công.');
        });
    }

    /**
     * Helper tìm user qua email hoặc phone
     */
    private function findUserByUsername(string $username)
    {
        $field = filter_var($username, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        return ($field === 'email')
            ? $this->authRepository->findByEmail($username)
            : $this->authRepository->findByPhone($username);
    }

    /**
     * Tự động sinh mã nhân viên: ví dụ STAFF-XXXXX
     */
    private function generateStaffCode(): string
    {
        return 'ST-' . strtoupper(Str::random(6));
    }
}
