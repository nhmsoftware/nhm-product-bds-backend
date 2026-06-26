<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Auth\DTO\ForgotPasswordDTO;
use App\Modules\Auth\DTO\LoginDTO;
use App\Modules\Auth\DTO\RegisterDTO;
use App\Modules\Auth\DTO\ResetPasswordDTO;
use App\Modules\Auth\DTO\VerifyOtpDTO;
use App\Modules\Auth\DTO\UpdateFcmTokenDTO;
use App\Modules\Auth\Events\UserRegistered;
use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Modules\EmployeeReferral\Models\Enums\ReferralStatus;
use App\Modules\EmployeeReferral\Models\Enums\ReferralType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Handles authentication core: register, login, OTP, password reset, logout, FCM token.
 */
final class AuthCoreService extends BaseService
{
    private const LOGIN_MAX_ATTEMPTS = 5;
    private const LOGIN_LOCKOUT_MINUTES = 15;

    public function __construct(
        private readonly AuthRepositoryInterface $authRepository,
        private readonly \App\Modules\EmployeeReferral\Interfaces\ReferralHistoryRepositoryInterface $referralHistoryRepository,
    ) {
    }

    public function register(RegisterDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
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

            $referralType = $this->detectReferralType($dto->referral_code, $dto->account_type);
            $referrer = null;

            if (!empty($dto->referral_code)) {
                $referrer = $this->authRepository->findByStaffCode($dto->referral_code);
                $this->validate($referrer !== null, 'Mã giới thiệu không hợp lệ hoặc không tồn tại.', 422);

                if ($referralType === ReferralType::CUSTOMER) {
                    $this->validate($dto->account_type === 'investor', 'Mã giới thiệu này dành cho Nhà đầu tư.', 422);
                } else {
                    $this->validate($dto->account_type === 'broker', 'Mã giới thiệu này dành cho Môi giới.', 422);
                }
            }

            $data = $dto->toArray();
            unset($data['account_type']);
            $data['password']   = Hash::make($dto->password);
            $data['staff_code'] = $this->generateStaffCode();
            $data['role']       = $dto->account_type === 'broker' ? UserRole::EMPLOYEE : UserRole::BUYER;
            $data['is_active']  = true;

            $user = $this->authRepository->create($data);

            if ($referrer) {
                $referralHistory = $this->referralHistoryRepository->findByReferrerAndPhone($referrer->id, $dto->phone);

                if ($referralHistory) {
                    $referralHistory->update([
                        'referee_id' => $user->id,
                        'referral_type' => ($referralType ?? ReferralType::RECRUITMENT)->value,
                        'status' => ReferralStatus::REGISTERED->value,
                        'registered_at' => now(),
                    ]);
                } else {
                    $this->referralHistoryRepository->create([
                        'referrer_id' => $referrer->id,
                        'referee_id' => $user->id,
                        'name' => $user->name,
                        'phone' => $user->phone,
                        'referral_type' => ($referralType ?? ReferralType::RECRUITMENT)->value,
                        'status' => ReferralStatus::REGISTERED->value,
                        'scanned_at' => now(),
                        'registered_at' => now(),
                    ]);
                }
            }

            event(new UserRegistered($user));

            return $this->success($user, 'Đăng ký thành công.');
        }, useTransaction: true);
    }

    public function login(LoginDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $field = filter_var($dto->username, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

            $user = ($field === 'email')
                ? $this->authRepository->findByEmail($dto->username)
                : $this->authRepository->findByPhone($dto->username);

            $this->validate($user !== null, 'Tài khoản không tồn tại.', 404);

            $lockKey = "login_lock:{$user->id}";
            $attemptsKey = "login_attempts:{$user->id}";

            if (Cache::has($lockKey)) {
                $lockedUntil = Carbon::parse(Cache::get($lockKey));
                $remainingMinutes = (int) ceil($lockedUntil->diffInSeconds(now()) / 60);
                $this->validate(
                    false,
                    "Tài khoản đã bị khóa tạm thời do nhập sai quá nhiều lần. Vui lòng thử lại sau {$remainingMinutes} phút.",
                    429
                );
            }

            $passwordCorrect = Hash::check($dto->password, $user->password);

            if (!$passwordCorrect) {
                $attempts = (int) Cache::get($attemptsKey, 0) + 1;
                Cache::put($attemptsKey, $attempts, now()->addMinutes(self::LOGIN_LOCKOUT_MINUTES));

                if ($attempts >= self::LOGIN_MAX_ATTEMPTS) {
                    Cache::put($lockKey, now()->addMinutes(self::LOGIN_LOCKOUT_MINUTES)->toDateTimeString(), now()->addMinutes(self::LOGIN_LOCKOUT_MINUTES));
                    Cache::forget($attemptsKey);
                    $this->validate(
                        false,
                        'Tài khoản đã bị khóa tạm thời do nhập sai mật khẩu quá nhiều lần. Vui lòng thử lại sau 15 phút.',
                        429
                    );
                }

                $this->validate(false, 'Thông tin đăng nhập không chính xác.', 401);
            }

            Cache::forget($attemptsKey);

            $this->validate((bool) $user->is_active, 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.', 403);

            $allowedRoles = [
                UserRole::EMPLOYEE->value,
                UserRole::MANAGER->value,
                UserRole::DIRECTOR->value,
                UserRole::CEO->value,
                UserRole::SUPER_ADMIN->value,
                UserRole::BUYER->value,
            ];
            $this->validate(in_array($user->role->value, $allowedRoles), 'Bạn không có quyền truy cập.', 403);

            $token = auth('api')->login($user);
            $this->validate($token !== false, 'Không thể tạo phiên đăng nhập.', 500);

            return $this->success([
                'access_token' => $token,
                'token_type'   => 'bearer',
                'expires_in'   => auth('api')->factory()->getTTL() * 60,
                'user'         => $user,
            ], 'Đăng nhập thành công.');
        });
    }

    public function forgotPassword(ForgotPasswordDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $user = $this->findUserByUsername($dto->username);
            $userExists = $user !== null && (bool) $user->is_active;

            $cooldownKey = "otp_cooldown:{$dto->username}";
            if (Cache::has($cooldownKey)) {
                $this->validate(false, 'Vui lòng đợi 60 giây trước khi yêu cầu mã mới.', 429);
            }

            $otp = (string) rand(100000, 999999);

            if (app()->environment('local')) {
                $otp = '123456';
            }

            Cache::put("otp:{$dto->username}", $otp, now()->addMinutes(5));
            Cache::put($cooldownKey, true, now()->addSeconds(60));
            Cache::put("otp_attempts:{$dto->username}", 0, now()->addMinutes(5));

            if ($userExists) {
                if (filter_var($dto->username, FILTER_VALIDATE_EMAIL)) {
                    try {
                        \Illuminate\Support\Facades\Mail::raw("Mã OTP quên mật khẩu của bạn là: {$otp}. Mã có hiệu lực trong vòng 5 phút.", function ($message) use ($dto) {
                            $message->to($dto->username)
                                ->subject("[" . config('app.name') . "] Mã OTP xác thực quên mật khẩu");
                        });
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error("Failed to send OTP email to {$dto->username}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
                    }
                } else {
                    \Illuminate\Support\Facades\Log::info("OTP for {$dto->username}: {$otp}");
                }
            }

            $responseData = null;
            if (app()->environment('local')) {
                $responseData = ['otp_code' => $otp];
            }

            return $this->success($responseData, 'Nếu thông tin bạn nhập hợp lệ, mã OTP đã được gửi.');
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
            $otpKey = "otp:{$dto->username}";
            $this->validate(Cache::get($otpKey) === $dto->otp, 'Mã OTP không hợp lệ hoặc đã hết hạn.', 400);

            $user = $this->findUserByUsername($dto->username);
            $this->validate($user !== null, 'Tài khoản không tồn tại.', 404);

            $this->validate(
                !Hash::check($dto->password, $user->password),
                'Mật khẩu mới không được trùng với mật khẩu cũ.',
                400
            );

            $this->authRepository->updateById($user->id, [
                'password' => Hash::make($dto->password),
            ]);

            Cache::forget($otpKey);
            Cache::forget("otp_attempts:{$dto->username}");

            auth('api')->invalidate(true);

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

    public function updateFcmToken(UpdateFcmTokenDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $user = $this->authRepository->findById($dto->userId);

            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản.', 404);

            $this->authRepository->updateById($dto->userId, [
                'fcm_token' => $dto->fcmToken,
            ]);

            return $this->success(
                ['fcm_token' => $dto->fcmToken],
                'Cập nhật token thông báo thành công.'
            );
        });
    }

    private function detectReferralType(?string $referralCode, string $accountType): ?ReferralType
    {
        $code = strtoupper(trim((string) $referralCode));

        if ($code === '') {
            return null;
        }

        if (str_starts_with($code, 'CUS-')) {
            return ReferralType::CUSTOMER;
        }

        if (str_starts_with($code, 'REC-')) {
            return ReferralType::RECRUITMENT;
        }

        return $accountType === 'broker' ? ReferralType::RECRUITMENT : ReferralType::CUSTOMER;
    }

    private function findUserByUsername(string $username)
    {
        $field = filter_var($username, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        return ($field === 'email')
            ? $this->authRepository->findByEmail($username)
            : $this->authRepository->findByPhone($username);
    }

    private function generateStaffCode(): string
    {
        return strtoupper(Str::random(6));
    }
}
