<?php

namespace App\Modules\Auth\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Auth\DTO\ForgotPasswordDTO;
use App\Modules\Auth\DTO\GetRewardPointHistoryDTO;
use App\Modules\Auth\DTO\GetTeamMembersDTO;
use App\Modules\Auth\DTO\LoginDTO;
use App\Modules\Auth\DTO\RegisterDTO;
use App\Modules\Auth\DTO\ResetPasswordDTO;
use App\Modules\Auth\DTO\UpdateProfileDTO;
use App\Modules\Auth\DTO\VerifyOtpDTO;
use App\Modules\Auth\DTO\GetTeamKpiDTO;
use App\Modules\Auth\DTO\GetEmployeeKpiDTO;
use App\Modules\Auth\DTO\GetDepartmentRankingDTO;
use App\Modules\Auth\DTO\UpdateFcmTokenDTO;

use App\Modules\Auth\Events\UserRegistered;
use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use App\Modules\Auth\Interfaces\AuthServiceInterface;
use App\Modules\Auth\Interfaces\RewardPointHistoryRepositoryInterface;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Modules\EmployeeReferral\Models\Enums\ReferralStatus;
use App\Modules\EmployeeReferral\Models\Enums\ReferralType;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class AuthService extends BaseService implements AuthServiceInterface
{
    public function __construct(
        private readonly AuthRepositoryInterface $authRepository,
        private readonly RewardPointHistoryRepositoryInterface $rewardPointHistoryRepository,
        private readonly \App\Modules\Area\Interfaces\LotDepositRequestRepositoryInterface $lotDepositRequestRepository,
        private readonly \App\Modules\SiteTour\Interfaces\SiteTourRepositoryInterface $siteTourRepository,
        private readonly \App\Modules\CustomerMeeting\Interfaces\CustomerMeetingRepositoryInterface $customerMeetingRepository,
        private readonly \App\Modules\EmployeeReferral\Interfaces\ReferralHistoryRepositoryInterface $referralHistoryRepository,
        private readonly \App\Modules\Attendance\Interfaces\AttendanceRepositoryInterface $attendanceRepository
    ) {
    }

    /**
     * Đăng ký tài khoản người dùng mới (UC-01).
     *
     * @param RegisterDTO $dto
     * @return ServiceReturn
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
            $data['role']       = UserRole::BUYER; // Mặc định là buyer khi đăng ký qua public form
            $data['is_active']  = true;

            // 3. Tạo user
            $user = $this->authRepository->create($data);

            // Xử lý Referral Code nếu có
            if (!empty($dto->referral_code)) {
                $employee = $this->authRepository->findByStaffCode($dto->referral_code);
                if ($employee) {
                    $referralHistory = $this->referralHistoryRepository->findByReferrerAndPhone($employee->id, $dto->phone);

                    if ($referralHistory) {
                        $referralHistory->update([
                            'referee_id' => $user->id,
                            'status' => ReferralStatus::REGISTERED->value,
                            'registered_at' => now(),
                        ]);
                    } else {
                        $this->referralHistoryRepository->create([
                            'referrer_id' => $employee->id,
                            'referee_id' => $user->id,
                            'name' => $user->name,
                            'phone' => $user->phone,
                            'referral_type' => ReferralType::RECRUITMENT->value,
                            'status' => ReferralStatus::REGISTERED->value,
                            'scanned_at' => now(),
                            'registered_at' => now(),
                        ]);
                    }
                }
            }

            // 4. Bắn Event
            event(new UserRegistered($user));

            return $this->success($user, 'Đăng ký thành công.');
        }, useTransaction: true);
    }

    /**
     * Đăng nhập hệ thống và cấp JWT token (UC-02).
     *
     * @param LoginDTO $dto
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function login(LoginDTO $dto): ServiceReturn
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

            // 5. Kiểm tra Role: Chỉ cho phép các role được định nghĩa
            $allowedRoles = [
                UserRole::EMPLOYEE->value,
                UserRole::MANAGER->value,
                UserRole::DIRECTOR->value,
                UserRole::CEO->value,
                UserRole::SUPER_ADMIN->value,
                UserRole::BUYER->value,
            ];
            $this->validate(
                in_array($user->role->value, $allowedRoles),
                'Bạn không có quyền truy cập.',
                403
            );

            // 6. Tạo token (Sử dụng JWT)
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

    /**
     * Yêu cầu gửi mã OTP quên mật khẩu (UC-03).
     *
     * @param ForgotPasswordDTO $dto
     * @return ServiceReturn
     * @throws \Throwable
     */
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

            $responseData = null;
            if (config('services.otp_expose') === true && !app()->isProduction()) {
                $responseData = ['otp_code' => $otp];
            }

            return $this->success($responseData, 'Mã OTP đã được gửi.');
        });
    }

    /**
     * Xác thực mã OTP người dùng nhập (UC-03).
     *
     * @param VerifyOtpDTO $dto
     * @return ServiceReturn
     * @throws \Throwable
     */
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

    /**
     * Đặt lại mật khẩu mới sau khi xác thực OTP thành công (UC-03).
     *
     * @param ResetPasswordDTO $dto
     * @return ServiceReturn
     * @throws \Throwable
     */
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

    /**
     * Đăng xuất hệ thống và vô hiệu hóa token (UC-05).
     *
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function logout(): ServiceReturn
    {
        return $this->execute(function () {
            auth('api')->logout();
            return $this->success(null, 'Đăng xuất thành công.');
        });
    }

    /**
     * Lấy thông tin hồ sơ cá nhân của người dùng (UC-030).
     *
     * @param string $userId
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function getProfile(string $userId): ServiceReturn
    {
        return $this->execute(function () use ($userId) {
            // 1. Tải thông tin tài khoản bằng repository
            $user = $this->authRepository->findById($userId);

            // A1 - Không tải được thông tin cá nhân
            $this->validate(
                $user !== null,
                'Không thể tải thông tin cá nhân. Vui lòng thử lại.',
                404
            );

            // A2 - Tài khoản bị khóa
            $this->validate(
                (bool) $user->is_active,
                'Tài khoản của bạn đã bị khóa.',
                403
            );

            // A3 - Thiếu một số thông tin cá nhân -> hiển thị "Chưa cập nhật."
            $profile = [
                'id' => (string) $user->id,
                'staff_code' => $user->staff_code ?: 'Chưa cập nhật.',
                'name' => $user->name ?: 'Chưa cập nhật.',
                'cccd' => $user->cccd ?: 'Chưa cập nhật.',
                'email' => $user->email ?: 'Chưa cập nhật.',
                'phone' => $user->phone ?: 'Chưa cập nhật.',
                'address' => $user->address ?: 'Chưa cập nhật.',
                'avatar' => $user->avatar,
                'role' => $user->role->serialize(),
                'is_active' => (bool) $user->is_active,
                'created_at' => $user->created_at ? $user->created_at->toIso8601String() : null,
            ];

            return $this->success($profile, 'Tải thông tin cá nhân thành công.');
        });
    }

    public function getDepartments(string $userId): ServiceReturn
    {
        return $this->execute(function () use ($userId) {
            $user = $this->authRepository->findById($userId);

            $this->validate(
                $user !== null,
                'Không tìm thấy thông tin người dùng.',
                404
            );

            $this->validate(
                (bool) $user->is_active,
                'Tài khoản của bạn đã bị khóa.',
                403
            );

            $departments = collect($this->authRepository->getActiveDepartmentNames())
                ->map(fn (string $department) => [
                    'label' => $department,
                    'value' => $department,
                ])
                ->values()
                ->all();

            return $this->success([
                'departments' => $departments,
            ], 'Tải danh sách phòng ban thành công.');
        });
    }

    /**
     * Cập nhật thông tin hồ sơ cá nhân của người dùng (UC-031).
     *
     * @param UpdateProfileDTO $dto
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function updateProfile(UpdateProfileDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // 1. Tải thông tin tài khoản
            $user = $this->authRepository->findById($dto->userId);

            // A1 - Không tải được thông tin cá nhân
            $this->validate(
                $user !== null,
                'Không thể tải thông tin cá nhân. Vui lòng thử lại.',
                404
            );

            // A2 - Tài khoản bị khóa
            $this->validate(
                (bool) $user->is_active,
                'Tài khoản của bạn đã bị khóa.',
                403
            );

            // 2. Cập nhật thông tin qua Repository
            $updated = $this->authRepository->updateById($dto->userId, $dto->toArray());

            // A5 - Lỗi cập nhật dữ liệu
            $this->validate(
                $updated !== false && $updated !== null,
                'Không thể cập nhật thông tin. Vui lòng thử lại.',
                500
            );

            // 3. Chuẩn bị hồ sơ mới để trả về
            $profile = [
                'id' => (string) $updated->id,
                'staff_code' => $updated->staff_code ?: 'Chưa cập nhật.',
                'name' => $updated->name ?: 'Chưa cập nhật.',
                'cccd' => $updated->cccd ?: 'Chưa cập nhật.',
                'email' => $updated->email ?: 'Chưa cập nhật.',
                'phone' => $updated->phone ?: 'Chưa cập nhật.',
                'address' => $updated->address ?: 'Chưa cập nhật.',
                'avatar' => $updated->avatar,
                'role' => $updated->role->serialize(),
                'is_active' => (bool) $updated->is_active,
                'created_at' => $updated->created_at ? $updated->created_at->toIso8601String() : null,
            ];

            return $this->success($profile, 'Cập nhật thông tin thành công.');
        }, useTransaction: true);
    }

    /**
     * Thay đổi mật khẩu tài khoản của người dùng (UC-032).
     *
     * @param \App\Modules\Auth\DTO\ChangePasswordDTO $dto
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function changePassword(\App\Modules\Auth\DTO\ChangePasswordDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // 1. Tải thông tin tài khoản
            $user = $this->authRepository->findById($dto->userId);

            // A1 - Không tải được thông tin cá nhân / User không tồn tại
            $this->validate(
                $user !== null,
                'Không thể tải thông tin cá nhân. Vui lòng thử lại.',
                404
            );

            // A2 - Tài khoản bị khóa
            $this->validate(
                (bool) $user->is_active,
                'Tài khoản của bạn đã bị khóa.',
                403
            );

            // A2 – Mật khẩu hiện tại không đúng
            $this->validate(
                \Illuminate\Support\Facades\Hash::check($dto->currentPassword, $user->password),
                'Mật khẩu hiện tại không chính xác.',
                400
            );

            // 2. Cập nhật mật khẩu mới qua Repository
            $updated = $this->authRepository->updateById($dto->userId, [
                'password' => \Illuminate\Support\Facades\Hash::make($dto->newPassword),
            ]);

            // A6 – Lỗi cập nhật mật khẩu
            $this->validate(
                $updated !== false && $updated !== null,
                'Không thể đổi mật khẩu. Vui lòng thử lại.',
                500
            );

            return $this->success(null, 'Đổi mật khẩu thành công.');
        }, useTransaction: true);
    }

    /**
     * Lấy thông tin hồ sơ nhân sự cá nhân (UC-033).
     *
     * @param string $userId
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function getEmployeeProfile(string $userId): ServiceReturn
    {
        return $this->execute(function () use ($userId) {
            // 1. Tải thông tin tài khoản kèm theo hồ sơ nhân sự
            $user = $this->authRepository->findById($userId, ['*'], ['employeeProfile']);

            // A1 – Không tải được dữ liệu hồ sơ / User không tồn tại
            $this->validate(
                $user !== null,
                'Không thể tải thông tin hồ sơ. Vui lòng thử lại.',
                404
            );

            // A6 – Tài khoản bị khóa
            $this->validate(
                (bool) $user->is_active,
                'Tài khoản của bạn đã bị khóa.',
                403
            );

            $ep = $user->employeeProfile;

            // Kiểm tra dữ liệu ngân hàng (A2)
            $hasBankDetails = $ep && $ep->bank_account_name && $ep->bank_account_number && $ep->bank_name;
            $bankMessage = $hasBankDetails ? null : 'Chưa cập nhật thông tin ngân hàng.';

            // Kiểm tra dữ liệu học vấn và kinh nghiệm (A3)
            $hasEducationDetails = $ep && ($ep->education || $ep->major || $ep->experience);
            $educationMessage = $hasEducationDetails ? null : 'Chưa cập nhật thông tin học vấn hoặc kinh nghiệm.';

            // Kiểm tra tài liệu đính kèm (A4)
            $hasAttachments = $ep && !empty($ep->attachments);
            $attachmentsMessage = $hasAttachments ? null : 'Chưa có tài liệu đính kèm.';
            $identityCard = $user->cccd ?: ($ep?->identity_card ?: null);

            $profileData = [
                'user' => [
                    'id'             => (string) $user->id,
                    'name'           => $user->name,
                    'cccd'           => $identityCard ?: 'Chưa cập nhật.',
                    'phone'          => $user->phone ?: 'Chưa cập nhật.',
                    'email'          => $user->email,
                    'avatar'         => $user->avatar,
                    'role'           => $user->role->serialize(), // Chức vụ
                    'address'        => $user->address ?: 'Chưa cập nhật.',
                ],
                'employee_details' => [
                    'employee_title'  => $ep?->employee_title ?: 'Chưa cập nhật.',
                    'identity_card'   => $identityCard ?: 'Chưa cập nhật.',
                    'dob'             => $ep?->dob ? $ep->dob->toDateString() : 'Chưa cập nhật.',
                ],
                'bank_info' => [
                    'bank_account_name'   => $ep?->bank_account_name ?: 'Chưa cập nhật.',
                    'bank_account_number' => $ep?->bank_account_number ?: 'Chưa cập nhật.',
                    'bank_name'           => $ep?->bank_name ?: 'Chưa cập nhật.',
                    'status_message'      => $bankMessage,
                ],
                'education_experience' => [
                    'education'       => $ep?->education ?: 'Chưa cập nhật.',
                    'major'           => $ep?->major ?: 'Chưa cập nhật.',
                    'experience'      => $ep?->experience ?: 'Chưa cập nhật.',
                    'status_message'  => $educationMessage,
                ],
                'attachments' => [
                    'list'            => $ep?->attachments ?: [],
                    'status_message'  => $attachmentsMessage,
                ]
            ];

            return $this->success($profileData, 'Tải hồ sơ nhân viên thành công.');
        });
    }

    /**
     * Cập nhật thông tin hồ sơ nhân sự cá nhân (UC-034).
     *
     * @param \App\Modules\Auth\DTO\UpdateEmployeeProfileDTO $dto
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function updateEmployeeProfile(\App\Modules\Auth\DTO\UpdateEmployeeProfileDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // 1. Tải thông tin tài khoản kèm hồ sơ nhân sự
            $user = $this->authRepository->findById($dto->userId, ['*'], ['employeeProfile']);

            // A5 – Lỗi cập nhật hồ sơ / User không tồn tại
            $this->validate(
                $user !== null,
                'Không thể cập nhật hồ sơ. Vui lòng thử lại.',
                404
            );

            // Tài khoản bị khóa
            $this->validate(
                (bool) $user->is_active,
                'Tài khoản của bạn đã bị khóa.',
                403
            );

            // 2. Cập nhật thông tin cơ bản trên bảng users
            $userData = [
                'name'    => $dto->name,
                'phone'   => $dto->phone,
                'email'   => $dto->email,
                'avatar'  => $dto->avatar,
                'address' => $dto->address,
            ];

            if ($dto->hasCccd) {
                $userData['cccd'] = $dto->cccd;
            }

            $userUpdated = $this->authRepository->updateById($dto->userId, $userData);

            // A5 – Lỗi cập nhật hồ sơ
            $this->validate(
                $userUpdated !== false && $userUpdated !== null,
                'Không thể cập nhật hồ sơ. Vui lòng thử lại.',
                500
            );

            // 3. Cập nhật thông tin chi tiết trên bảng employee_profiles
            $epData = [
                'employee_title'      => $dto->employeeTitle,
                'dob'                 => $dto->dob,
                'bank_account_name'   => $dto->bankAccountName,
                'bank_account_number' => $dto->bankAccountNumber,
                'bank_name'           => $dto->bankName,
                'education'           => $dto->education,
                'major'               => $dto->major,
                'experience'          => $dto->experience,
            ];

            if ($dto->hasCccd) {
                $epData['identity_card'] = $dto->cccd;
            }

            if ($user->employeeProfile) {
                $epUpdated = $user->employeeProfile->update($epData);
            } else {
                $epUpdated = $user->employeeProfile()->create($epData);
            }

            // A5 – Lỗi cập nhật hồ sơ
            $this->validate(
                $epUpdated !== false && $epUpdated !== null,
                'Không thể cập nhật hồ sơ. Vui lòng thử lại.',
                500
            );

            // 4. Trả về thông tin hồ sơ nhân sự mới nhất đã cập nhật
            $freshUser = $this->authRepository->findById($dto->userId, ['*'], ['employeeProfile']);
            $ep = $freshUser->employeeProfile;

            $hasBankDetails = $ep && $ep->bank_account_name && $ep->bank_account_number && $ep->bank_name;
            $bankMessage = $hasBankDetails ? null : 'Chưa cập nhật thông tin ngân hàng.';

            $hasEducationDetails = $ep && ($ep->education || $ep->major || $ep->experience);
            $educationMessage = $hasEducationDetails ? null : 'Chưa cập nhật thông tin học vấn hoặc kinh nghiệm.';

            $hasAttachments = $ep && !empty($ep->attachments);
            $attachmentsMessage = $hasAttachments ? null : 'Chưa có tài liệu đính kèm.';
            $identityCard = $freshUser->cccd ?: ($ep?->identity_card ?: null);

            $profileData = [
                'user' => [
                    'id'             => (string) $freshUser->id,
                    'name'           => $freshUser->name,
                    'cccd'           => $identityCard ?: 'Chưa cập nhật.',
                    'phone'          => $freshUser->phone ?: 'Chưa cập nhật.',
                    'email'          => $freshUser->email,
                    'avatar'         => $freshUser->avatar,
                    'role'           => $freshUser->role->serialize(),
                    'address'        => $freshUser->address ?: 'Chưa cập nhật.',
                ],
                'employee_details' => [
                    'employee_title'  => $ep?->employee_title ?: 'Chưa cập nhật.',
                    'identity_card'   => $identityCard ?: 'Chưa cập nhật.',
                    'dob'             => $ep?->dob ? $ep->dob->toDateString() : 'Chưa cập nhật.',
                ],
                'bank_info' => [
                    'bank_account_name'   => $ep?->bank_account_name ?: 'Chưa cập nhật.',
                    'bank_account_number' => $ep?->bank_account_number ?: 'Chưa cập nhật.',
                    'bank_name'           => $ep?->bank_name ?: 'Chưa cập nhật.',
                    'status_message'      => $bankMessage,
                ],
                'education_experience' => [
                    'education'       => $ep?->education ?: 'Chưa cập nhật.',
                    'major'           => $ep?->major ?: 'Chưa cập nhật.',
                    'experience'      => $ep?->experience ?: 'Chưa cập nhật.',
                    'status_message'  => $educationMessage,
                ],
                'attachments' => [
                    'list'            => $ep?->attachments ?: [],
                    'status_message'  => $attachmentsMessage,
                ]
            ];

            return $this->success($profileData, 'Cập nhật hồ sơ thành công.');
        }, useTransaction: true);
    }

    public function uploadEmployeeAvatar(\App\Modules\Auth\DTO\UploadEmployeeAvatarDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $user = $this->authRepository->findById($dto->userId);

            $this->validate(
                $user !== null,
                'Không thể cập nhật ảnh đại diện. Vui lòng thử lại.',
                404
            );

            $this->validate(
                (bool) $user->is_active,
                'Tài khoản của bạn đã bị khóa.',
                403
            );

            $path = $dto->avatar->store('avatars', 'public');
            $this->validate(
                $path !== false && $path !== null,
                'Không thể tải ảnh đại diện lên. Vui lòng thử lại.',
                500
            );

            $avatarUrl = \Illuminate\Support\Facades\Storage::url($path);
            $oldAvatar = is_string($user->avatar) ? $user->avatar : '';

            $updated = $this->authRepository->updateById($dto->userId, [
                'avatar' => $avatarUrl,
            ]);

            $this->validate(
                $updated !== false && $updated !== null,
                'Không thể cập nhật ảnh đại diện. Vui lòng thử lại.',
                500
            );

            $oldAvatarPath = parse_url($oldAvatar, PHP_URL_PATH);
            if (is_string($oldAvatarPath) && str_starts_with($oldAvatarPath, '/storage/avatars/')) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete(str_replace('/storage/', '', $oldAvatarPath));
            }

            return $this->success([
                'avatar' => $avatarUrl,
                'user' => [
                    'id' => (string) $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar' => $avatarUrl,
                ],
            ], 'Cập nhật ảnh đại diện thành công.');
        }, useTransaction: true);
    }

    /**
     * Tải lên tài liệu hồ sơ nhân sự (UC-035).
     *
     * @param \App\Modules\Auth\DTO\UploadEmployeeDocumentDTO $dto
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function uploadEmployeeDocument(\App\Modules\Auth\DTO\UploadEmployeeDocumentDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // 1. Tải thông tin tài khoản kèm hồ sơ nhân sự
            $user = $this->authRepository->findById($dto->userId, ['*'], ['employeeProfile']);

            $this->validate(
                $user !== null,
                'Không thể tải tài liệu lên. Vui lòng thử lại.',
                404
            );

            // Tài khoản bị khóa
            $this->validate(
                (bool) $user->is_active,
                'Tài khoản của bạn đã bị khóa.',
                403
            );

            // 2. Lưu file vào storage
            $path = $dto->file->store('employee_documents', 'public');

            // A5 - Lỗi tải file lên hệ thống
            $this->validate(
                $path !== false && $path !== null,
                'Không thể tải tài liệu lên. Vui lòng thử lại.',
                500
            );

            // 3. Đảm bảo nhân viên đã có bản ghi profile
            if (!$user->employeeProfile) {
                $user->employeeProfile()->create([]);
                $user->load('employeeProfile'); // Nạp lại quan hệ
            }

            // 4. Cập nhật mảng attachments
            $attachments = $user->employeeProfile->attachments ?? [];
            $newDocument = [
                'type' => $dto->type,
                'name' => $dto->file->getClientOriginalName(),
                'url'  => \Illuminate\Support\Facades\Storage::url($path),
                'created_at' => now()->toIso8601String(),
            ];
            $attachments[] = $newDocument;

            $epUpdated = $user->employeeProfile->update(['attachments' => $attachments]);

            $this->validate(
                $epUpdated !== false && $epUpdated !== null,
                'Không thể tải tài liệu lên. Vui lòng thử lại.',
                500
            );

            return $this->success([
                'document' => $newDocument,
                'list' => $attachments
            ], 'Tải tài liệu thành công.');
        }, useTransaction: true);
    }

    /**
     * Lấy thông tin tổng quan điểm thưởng của nhân viên (UC-105).
     *
     * @param string $userId
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function getRewardPointOverview(string $userId): ServiceReturn
    {
        return $this->execute(function () use ($userId) {
            $user = $this->authRepository->findById($userId, ['*'], ['employeeProfile']);

            $this->validate(
                $user !== null,
                'Không tìm thấy thông tin người dùng.',
                404
            );

            // A4 - Người dùng không có quyền truy cập
            $allowedRoles = [
                UserRole::EMPLOYEE->value,
                UserRole::MANAGER->value,
                UserRole::DIRECTOR->value,
            ];
            $this->validate(
                in_array($user->role->value, $allowedRoles),
                'Bạn không có quyền truy cập chức năng này.',
                403
            );

            $ep = $user->employeeProfile;
            $totalPoints = (int) ($ep?->reward_points ?? 0);
            $rank = $this->resolveRewardRank($totalPoints);

            // Lấy điểm thưởng trong tháng
            $currentMonthPoints = $this->rewardPointHistoryRepository->calculateCurrentMonthPoints($userId);

            // Tính tiến độ mục tiêu quý (Ví dụ mặc định: 100 điểm = 100%)
            $quarterPoints = $this->rewardPointHistoryRepository->calculateQuarterPoints($userId);

            $targetPoints = 100; // Có thể thay đổi theo config
            $quarterProgress = $quarterPoints > 0 ? round(($quarterPoints / $targetPoints) * 100, 2) : 0;
            if ($quarterProgress > 100) $quarterProgress = 100;

            $overview = [
                'total_points' => $totalPoints,
                'kpi_stars' => $ep?->kpi_stars ?? 0,
                'rank' => $rank,
                'current_month_points' => $currentMonthPoints,
                'quarter_progress_percent' => $quarterProgress,
                'quarter_points' => $quarterPoints,
                'quarter_target' => $targetPoints,
            ];

            return $this->success($overview, 'Tải dữ liệu tổng quan thành công.');
        });
    }

    private function resolveRewardRank(int $totalPoints): array
    {
        if ($totalPoints >= 1500) {
            return ['id' => 4, 'label' => 'Bạch kim'];
        }

        if ($totalPoints >= 800) {
            return ['id' => 3, 'label' => 'Vàng'];
        }

        if ($totalPoints >= 500) {
            return ['id' => 2, 'label' => 'Bạc'];
        }

        return ['id' => 1, 'label' => 'Đồng'];
    }

    /**
     * Lấy lịch sử điểm thưởng của nhân viên (UC-105).
     *
     * @param GetRewardPointHistoryDTO $dto
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function getRewardPointHistory(GetRewardPointHistoryDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $user = $this->authRepository->findById($dto->userId);

            $this->validate(
                $user !== null,
                'Không tìm thấy thông tin người dùng.',
                404
            );

            $allowedRoles = [
                UserRole::EMPLOYEE->value,
                UserRole::MANAGER->value,
                UserRole::DIRECTOR->value,
            ];
            $this->validate(
                in_array($user->role->value, $allowedRoles),
                'Bạn không có quyền truy cập chức năng này.',
                403
            );

            $histories = $this->rewardPointHistoryRepository->getHistoriesPaginated(
                $dto->userId,
                $dto->fromDate,
                $dto->toDate,
                $dto->perPage
            );

            // A1 - Chưa có dữ liệu
            if ($histories->isEmpty() && !$dto->fromDate && !$dto->toDate) {
                return $this->success($histories, 'Chưa có dữ liệu điểm thưởng.');
            }

            // A2 - Không tìm thấy dữ liệu phù hợp
            if ($histories->isEmpty()) {
                return $this->success($histories, 'Không tìm thấy dữ liệu phù hợp.');
            }

            return $this->success($histories, 'Tải dữ liệu lịch sử điểm thưởng thành công.');
        });
    }

    /**
     * Lấy thông tin tổng quan phòng ban/khu vực (UC-106).
     *
     * @param string $userId
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function getTeamOverview(string $userId): ServiceReturn
    {
        return $this->execute(function () use ($userId) {
            $user = $this->authRepository->findById($userId);

            $this->validate(
                $user !== null,
                'Không tìm thấy thông tin người dùng.',
                404
            );

            // A3 - Người dùng không có quyền xem danh sách nhân viên
            $allowedRoles = [
                UserRole::MANAGER->value,
                UserRole::DIRECTOR->value,
            ];
            $this->validate(
                in_array($user->role->value, $allowedRoles),
                'Bạn không có quyền truy cập chức năng này.',
                403
            );

            $teamName = '';

            if ($user->role === UserRole::MANAGER) {
                $teamName = $user->department;
            } elseif ($user->role === UserRole::DIRECTOR) {
                $teamName = $user->area;
            }

            $memberCount = $this->authRepository->countActiveTeamMembers($user);

            $overview = [
                'team_name' => $teamName ?: 'Chưa cập nhật',
                'description' => 'Phòng ban/Khu vực ' . ($teamName ?: 'Chưa cập nhật'),
                'member_count' => $memberCount,
                'manager_name' => $user->name,
            ];

            return $this->success($overview, 'Tải thông tin tổng quan thành công.');
        });
    }

    /**
     * Lấy danh sách nhân viên trong phòng ban/khu vực (UC-106).
     *
     * @param GetTeamMembersDTO $dto
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function getTeamMembers(GetTeamMembersDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $user = $this->authRepository->findById($dto->userId);

            $this->validate(
                $user !== null,
                'Không tìm thấy thông tin người dùng.',
                404
            );

            // A3 - Người dùng không có quyền xem danh sách nhân viên
            $allowedRoles = [
                UserRole::MANAGER->value,
                UserRole::DIRECTOR->value,
            ];
            $this->validate(
                in_array($user->role->value, $allowedRoles),
                'Bạn không có quyền truy cập chức năng này.',
                403
            );

            $members = $this->authRepository->getActiveTeamMembers(
                $user,
                $dto->search,
                $dto->jobPosition,
                $dto->perPage
            );

            // A1 - Không có nhân viên trong phòng ban
            if ($members->isEmpty() && !$dto->search && !$dto->jobPosition) {
                return $this->success($members, 'Chưa có nhân viên trong phòng ban.');
            }

            // A2 - Không tìm thấy nhân viên phù hợp
            if ($members->isEmpty()) {
                return $this->success($members, 'Không tìm thấy nhân viên phù hợp.');
            }

            // Transform data for UI
            $members->getCollection()->transform(function ($member) {
                return [
                    'id' => (string) $member->id,
                    'staff_code' => $member->staff_code,
                    'name' => $member->name,
                    'job_position' => $member->job_position,
                    'phone' => $member->phone,
                    'avatar' => $member->avatar,
                ];
            });

            return $this->success($members, 'Tải danh sách nhân viên thành công.');
        });
    }

    /**
     * Helper tìm user qua email hoặc phone.
     *
     * @param string $username
     * @return User|null
     */
    private function findUserByUsername(string $username)
    {
        $field = filter_var($username, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        return ($field === 'email')
            ? $this->authRepository->findByEmail($username)
            : $this->authRepository->findByPhone($username);
    }

    /**
     * Tự động sinh mã nhân viên: ví dụ STAFF-XXXXX.
     *
     * @return string
     */
    private function generateStaffCode(): string
    {
        return 'ST-' . strtoupper(Str::random(6));
    }

    /**
     * Lấy thông tin tổng quan KPI của phòng ban/khu vực (UC-107).
     *
     * @param GetTeamKpiDTO $dto
     * @return ServiceReturn
     */
    public function getTeamKpiOverview(GetTeamKpiDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $user = $this->authRepository->findById($dto->userId);

            $this->validate(
                $user !== null,
                'Không tìm thấy thông tin người dùng.',
                404
            );

            // A3 - Người dùng không có quyền xem KPI đội nhóm
            $allowedRoles = [
                UserRole::MANAGER->value,
                UserRole::DIRECTOR->value,
            ];
            $this->validate(
                in_array($user->role->value, $allowedRoles),
                'Bạn không có quyền truy cập chức năng này.',
                403
            );

            // Lấy danh sách nhân viên của phòng ban/khu vực (chỉ lấy role EMPLOYEE)
            $members = $this->authRepository->getScopedActiveEmployees($user);

            // A1 - Không có dữ liệu KPI
            if ($members->isEmpty()) {
                return $this->success([
                    'total_kpi_points' => 0,
                    'total_transactions' => 0,
                    'total_tours' => 0,
                    'total_meetings' => 0,
                    'total_referrals' => 0,
                ], 'Chưa có dữ liệu KPI đội nhóm.');
            }

            $userIds = $members->pluck('id')->toArray();

            // Tính toán tổng các chỉ số
            $totalTransactions = $this->lotDepositRequestRepository->countCompletedTransactions($userIds, $dto->fromDate, $dto->toDate);
            $totalTours = $this->siteTourRepository->countSiteTours($userIds, $dto->fromDate, $dto->toDate);
            $totalMeetings = $this->customerMeetingRepository->countCustomerMeetings($userIds, $dto->fromDate, $dto->toDate);
            $totalReferrals = $this->referralHistoryRepository->countSuccessfulReferralsForUsers($userIds, $dto->fromDate, $dto->toDate);

            // Lấy ngày công và vắng để tính tổng KPI điểm
            $workDaysQuery = $this->attendanceRepository->countWorkDaysByUsers($userIds, $dto->fromDate, $dto->toDate);
            $absencesQuery = $this->attendanceRepository->countFixedScheduleAbsencesByUsers($userIds, $dto->fromDate, $dto->toDate);
            $transactionsByEmployee = $this->lotDepositRequestRepository->countCompletedTransactionsByUsers($userIds, $dto->fromDate, $dto->toDate);
            $toursByEmployee = $this->siteTourRepository->countSiteToursByUsers($userIds, $dto->fromDate, $dto->toDate);
            $meetingsByEmployee = $this->customerMeetingRepository->countCustomerMeetingsByUsers($userIds, $dto->fromDate, $dto->toDate);
            $referralsByEmployee = $this->referralHistoryRepository->countSuccessfulReferralsByUsers($userIds, $dto->fromDate, $dto->toDate);

            $totalKpiPoints = 0;
            foreach ($userIds as $mId) {
                $mTransactions = $transactionsByEmployee->get($mId, 0);
                $mTours = $toursByEmployee->get($mId, 0);
                $mMeetings = $meetingsByEmployee->get($mId, 0);
                $mReferrals = $referralsByEmployee->get($mId, 0);
                $mWorkDays = $workDaysQuery->get($mId, 0);
                $mAbsences = $absencesQuery->get($mId, 0);

                $kpi = ($mTransactions * 10)
                    + ($mTours * 1)
                    + ($mMeetings * 0.5)
                    + ($mReferrals * 1)
                    + floor($mWorkDays / 5)
                    - ($mAbsences * 0.5);

                $totalKpiPoints += $kpi;
            }

            return $this->success([
                'total_kpi_points' => $totalKpiPoints,
                'total_transactions' => $totalTransactions,
                'total_tours' => $totalTours,
                'total_meetings' => $totalMeetings,
                'total_referrals' => $totalReferrals,
            ], 'Tải dữ liệu tổng quan KPI thành công.');
        });
    }

    /**
     * Lấy bảng xếp hạng KPI của phòng ban/khu vực (UC-107).
     *
     * @param GetTeamKpiDTO $dto
     * @return ServiceReturn
     */
    public function getTeamKpiLeaderboard(GetTeamKpiDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $user = $this->authRepository->findById($dto->userId);

            $this->validate(
                $user !== null,
                'Không tìm thấy thông tin người dùng.',
                404
            );

            // A3 - Người dùng không có quyền xem KPI đội nhóm
            $allowedRoles = [
                UserRole::MANAGER->value,
                UserRole::DIRECTOR->value,
            ];
            $this->validate(
                in_array($user->role->value, $allowedRoles),
                'Bạn không có quyền truy cập chức năng này.',
                403
            );

            // Áp dụng scope đội nhóm, tìm kiếm và vị trí công việc tại Repository
            $members = $this->authRepository->getFilteredScopedActiveEmployees(
                $user,
                $dto->search,
                $dto->jobPosition,
                true
            );

            // A2 - Không tìm thấy dữ liệu phù hợp
            if ($members->isEmpty()) {
                $paginated = new \Illuminate\Pagination\LengthAwarePaginator([], 0, $dto->perPage);
                return $this->success(
                    $paginated,
                    'Không tìm thấy dữ liệu phù hợp.'
                );
            }

            $userIds = $members->pluck('id')->toArray();

            // Lấy các chỉ số hoạt động hàng loạt
            $transactions = $this->lotDepositRequestRepository->countCompletedTransactionsByUsers($userIds, $dto->fromDate, $dto->toDate);
            $tours = $this->siteTourRepository->countSiteToursByUsers($userIds, $dto->fromDate, $dto->toDate);
            $meetings = $this->customerMeetingRepository->countCustomerMeetingsByUsers($userIds, $dto->fromDate, $dto->toDate);
            $referrals = $this->referralHistoryRepository->countSuccessfulReferralsByUsers($userIds, $dto->fromDate, $dto->toDate);
            $workDays = $this->attendanceRepository->countWorkDaysByUsers($userIds, $dto->fromDate, $dto->toDate);
            $absences = $this->attendanceRepository->countFixedScheduleAbsencesByUsers($userIds, $dto->fromDate, $dto->toDate);
            $stars = $this->rewardPointHistoryRepository->sumStarsByUsersAndDateRange($userIds, $dto->fromDate, $dto->toDate);

            // Tính toán và định dạng dữ liệu cho từng nhân viên
            $rankedList = $members->map(function ($member) use ($transactions, $tours, $meetings, $referrals, $workDays, $absences, $stars, $dto) {
                $mId = (string) $member->id;
                $userTransactions = $transactions->get($mId, 0);
                $userTours = $tours->get($mId, 0);
                $userMeetings = $meetings->get($mId, 0);
                $userReferrals = $referrals->get($mId, 0);
                $userWorkDays = $workDays->get($mId, 0);
                $userAbsences = $absences->get($mId, 0);

                $kpiPoints = ($userTransactions * 10)
                    + ($userTours * 1)
                    + ($userMeetings * 0.5)
                    + ($userReferrals * 1)
                    + floor($userWorkDays / 5)
                    - ($userAbsences * 0.5);

                if ($dto->fromDate || $dto->toDate) {
                    $kpiStars = (int) $stars->get($mId, 0);
                } else {
                    $kpiStars = (int) ($member->employeeProfile->kpi_stars ?? 0);
                }

                return [
                    'id' => $mId,
                    'staff_code' => $member->staff_code,
                    'name' => $member->name,
                    'job_position' => $member->job_position,
                    'avatar' => $member->avatar,
                    'total_kpi_points' => $kpiPoints,
                    'successful_transactions' => $userTransactions,
                    'kpi_stars' => $kpiStars,
                ];
            });

            // Sắp xếp giảm dần theo KPI điểm, nếu bằng nhau thì theo số giao dịch, sau đó theo tên
            $sorted = $rankedList->sort(function ($a, $b) {
                if ($b['total_kpi_points'] <=> $a['total_kpi_points']) {
                    return $b['total_kpi_points'] <=> $a['total_kpi_points'];
                }
                if ($b['successful_transactions'] <=> $a['successful_transactions']) {
                    return $b['successful_transactions'] <=> $a['successful_transactions'];
                }
                return strcmp($a['name'], $b['name']);
            })->values();

            // Gán Rank toàn cục
            $ranked = $sorted->map(function ($item, $index) {
                $item['rank'] = $index + 1;
                return $item;
            });

            // Phân trang
            $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
            $slice = $ranked->slice(($currentPage - 1) * $dto->perPage, $dto->perPage)->values();
            $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
                $slice,
                $ranked->count(),
                $dto->perPage,
                $currentPage,
                ['path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath()]
            );

            return $this->success($paginated, 'Tải bảng xếp hạng KPI thành công.');
        });
    }

    /**
     * Lấy chi tiết KPI và lịch sử điểm thưởng của một nhân viên (UC-107).
     *
     * @param GetEmployeeKpiDTO $dto
     * @return ServiceReturn
     */
    public function getEmployeeKpiDetails(GetEmployeeKpiDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $manager = $this->authRepository->findById($dto->managerId);
            $this->validate($manager !== null, 'Không tìm thấy thông tin người quản lý.', 404);

            // A3 - Người dùng không có quyền xem KPI đội nhóm
            $allowedRoles = [
                UserRole::MANAGER->value,
                UserRole::DIRECTOR->value,
            ];
            $this->validate(
                in_array($manager->role->value, $allowedRoles),
                'Bạn không có quyền truy cập chức năng này.',
                403
            );

            $employee = $this->authRepository->findById($dto->employeeId);
            $this->validate($employee !== null, 'Không tìm thấy thông tin nhân viên.', 404);

            // Kiểm tra nhân viên có cùng phòng ban/khu vực không
            if ($manager->role === UserRole::MANAGER) {
                $this->validate(
                    $employee->department === $manager->department,
                    'Bạn không có quyền truy cập chức năng này.',
                    403
                );
            } elseif ($manager->role === UserRole::DIRECTOR) {
                $this->validate(
                    $employee->area === $manager->area,
                    'Bạn không có quyền truy cập chức năng này.',
                    403
                );
            }

            // Tính toán chi tiết các chỉ số
            $userTransactions = $this->lotDepositRequestRepository->countCompletedTransactions($dto->employeeId, $dto->fromDate, $dto->toDate);
            $userTours = $this->siteTourRepository->countSiteTours($dto->employeeId, $dto->fromDate, $dto->toDate);
            $userMeetings = $this->customerMeetingRepository->countCustomerMeetings($dto->employeeId, $dto->fromDate, $dto->toDate);
            $userReferrals = $this->referralHistoryRepository->countSuccessfulReferralsForUsers($dto->employeeId, $dto->fromDate, $dto->toDate);
            $userWorkDays = $this->attendanceRepository->countWorkDays($dto->employeeId, $dto->fromDate, $dto->toDate);
            $userAbsences = $this->attendanceRepository->countFixedScheduleAbsences($dto->employeeId, $dto->fromDate, $dto->toDate);

            $kpiPoints = ($userTransactions * 10)
                + ($userTours * 1)
                + ($userMeetings * 0.5)
                + ($userReferrals * 1)
                + floor($userWorkDays / 5)
                - ($userAbsences * 0.5);

            if ($dto->fromDate || $dto->toDate) {
                $totalStars = $this->rewardPointHistoryRepository->sumStarsByUserAndDateRange(
                    $dto->employeeId,
                    $dto->fromDate,
                    $dto->toDate
                );
            } else {
                $totalStars = (int) ($employee->employeeProfile->kpi_stars ?? 0);
            }

            // Lấy lịch sử điểm thưởng của nhân viên
            $history = $this->rewardPointHistoryRepository->getHistoriesPaginated(
                $dto->employeeId,
                $dto->fromDate,
                $dto->toDate,
                $dto->perPage
            );

            $result = [
                'employee' => [
                    'id' => (string) $employee->id,
                    'staff_code' => $employee->staff_code,
                    'name' => $employee->name,
                    'job_position' => $employee->job_position,
                    'avatar' => $employee->avatar,
                    'department' => $employee->department,
                    'area' => $employee->area,
                ],
                'kpi_summary' => [
                    'total_kpi_points' => $kpiPoints,
                    'kpi_stars' => $totalStars,
                    'transactions_count' => $userTransactions,
                    'tours_count' => $userTours,
                    'meetings_count' => $userMeetings,
                    'referrals_count' => $userReferrals,
                    'work_days' => $userWorkDays,
                    'absences' => $userAbsences,
                ],
                'reward_history' => $history,
            ];

            return $this->success($result, 'Tải chi tiết KPI nhân viên thành công.');
        });
    }

    /**
     * Lấy bảng xếp hạng phòng ban (UC-108).
     *
     * @param GetDepartmentRankingDTO $dto
     * @return ServiceReturn
     */
    public function getDepartmentRanking(GetDepartmentRankingDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $user = $this->authRepository->findById($dto->userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin người dùng.', 404);

            // A3 - Người dùng không có quyền xem ranking
            $allowedRoles = [
                UserRole::MANAGER->value,
                UserRole::DIRECTOR->value,
                UserRole::CEO->value,
            ];
            $this->validate(
                in_array($user->role->value, $allowedRoles),
                'Bạn không có quyền truy cập chức năng này.',
                403
            );

            // Nếu không có bất kỳ phòng ban nào trong hệ thống trước khi lọc:
            $hasAnyDept = $this->authRepository->hasActiveEmployeesWithDepartment();
            if (!$hasAnyDept) {
                $paginated = new \Illuminate\Pagination\LengthAwarePaginator([], 0, $dto->perPage);
                return $this->success($paginated, 'Chưa có dữ liệu xếp hạng phòng ban.');
            }

            // Áp dụng lọc theo Area tại Repository
            $users = $this->authRepository->getActiveEmployeesWithDepartment($dto->area);

            // Nếu sau khi lọc không tìm thấy dữ liệu phù hợp:
            if ($users->isEmpty()) {
                $paginated = new \Illuminate\Pagination\LengthAwarePaginator([], 0, $dto->perPage);
                return $this->success($paginated, 'Không tìm thấy dữ liệu phù hợp.');
            }

            // Lọc ngày công
            $year = $dto->year ?? (int) now()->year;
            $fromDate = null;
            $toDate = null;
            if ($dto->month) {
                $fromDate = \Carbon\Carbon::create($year, $dto->month, 1)->startOfMonth()->toDateString();
                $toDate = \Carbon\Carbon::create($year, $dto->month, 1)->endOfMonth()->toDateString();
            } elseif ($dto->quarter) {
                $startMonth = ($dto->quarter - 1) * 3 + 1;
                $fromDate = \Carbon\Carbon::create($year, $startMonth, 1)->startOfMonth()->toDateString();
                $toDate = \Carbon\Carbon::create($year, $startMonth + 2, 1)->endOfMonth()->toDateString();
            }

            $usersByDept = $users->groupBy('department');
            $deptData = [];

            foreach ($usersByDept as $deptName => $deptUsers) {
                $userIds = $deptUsers->pluck('id')->toArray();

                // 1. Giao dịch công chứng thành công
                $deptTransactions = $this->lotDepositRequestRepository->countCompletedTransactions($userIds, $fromDate, $toDate);

                // 2. Lượt dẫn khách
                $deptTours = $this->siteTourRepository->countSiteTours($userIds, $fromDate, $toDate);

                // 3. Lượt gặp khách
                $deptMeetings = $this->customerMeetingRepository->countCustomerMeetings($userIds, $fromDate, $toDate);

                // 4. Referral nhân sự giới thiệu thành công
                $deptReferrals = $this->referralHistoryRepository->countSuccessfulReferralsForUsers($userIds, $fromDate, $toDate);

                // 5. Điểm danh / Ngày công
                $workDaysMap = $this->attendanceRepository->countWorkDaysByUsers($userIds, $fromDate, $toDate);
                $absencesMap = $this->attendanceRepository->countFixedScheduleAbsencesByUsers($userIds, $fromDate, $toDate);

                // 6. Số lượng sao
                $starsMap = $this->rewardPointHistoryRepository->sumStarsByUsersAndDateRange($userIds, $fromDate, $toDate);

                // Tính toán KPI và sao của từng nhân viên rồi cộng lại
                $totalKpiPoints = 0;
                $totalStars = 0;

                foreach ($userIds as $uId) {
                    $uTransactions = $this->lotDepositRequestRepository->countCompletedTransactions($uId, $fromDate, $toDate);

                    $uTours = $this->siteTourRepository->countSiteTours($uId, $fromDate, $toDate);

                    $uMeetings = $this->customerMeetingRepository->countCustomerMeetings($uId, $fromDate, $toDate);

                    $uReferrals = $this->referralHistoryRepository->countSuccessfulReferralsForUsers($uId, $fromDate, $toDate);

                    $uWorkDays = $workDaysMap->get($uId, 0);
                    $uAbsences = $absencesMap->get($uId, 0);

                    $kpi = ($uTransactions * 10)
                        + ($uTours * 1)
                        + ($uMeetings * 0.5)
                        + ($uReferrals * 1)
                        + floor($uWorkDays / 5)
                        - ($uAbsences * 0.5);

                    $totalKpiPoints += $kpi;

                    if ($fromDate || $toDate) {
                        $totalStars += (int) $starsMap->get($uId, 0);
                    } else {
                        // All-time: lấy từ profile
                        $userObj = $deptUsers->firstWhere('id', $uId);
                        $totalStars += (int) ($userObj->employeeProfile->kpi_stars ?? 0);
                    }
                }

                $deptData[] = [
                    'department' => $deptName,
                    'total_kpi_points' => $totalKpiPoints,
                    'successful_transactions' => $deptTransactions,
                    'kpi_stars' => $totalStars,
                    'total_tours' => $deptTours,
                    'total_meetings' => $deptMeetings,
                    'total_referrals' => $deptReferrals,
                ];
            }

            // Sắp xếp các phòng ban
            $sortedDepts = collect($deptData)->sort(function ($a, $b) {
                if ($b['total_kpi_points'] <=> $a['total_kpi_points']) {
                    return $b['total_kpi_points'] <=> $a['total_kpi_points'];
                }
                if ($b['successful_transactions'] <=> $a['successful_transactions']) {
                    return $b['successful_transactions'] <=> $a['successful_transactions'];
                }
                return strcmp($a['department'], $b['department']);
            })->values();

            // Gán Rank
            $rankedDepts = $sortedDepts->map(function ($item, $index) {
                $item['rank'] = $index + 1;
                return $item;
            });

            // Phân trang
            $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
            $slice = $rankedDepts->slice(($currentPage - 1) * $dto->perPage, $dto->perPage)->values();
            $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
                $slice,
                $rankedDepts->count(),
                $dto->perPage,
                $currentPage,
                ['path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath()]
            );

            return $this->success($paginated, 'Tải bảng xếp hạng phòng ban thành công.');
        });
    }

    /**
     * Lấy chi tiết KPI của phòng ban (UC-108).
     *
     * @param string $departmentName
     * @param GetDepartmentRankingDTO $dto
     * @return ServiceReturn
     */
    public function getDepartmentKpiDetails(string $departmentName, GetDepartmentRankingDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($departmentName, $dto) {
            $user = $this->authRepository->findById($dto->userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin người dùng.', 404);

            // A3 - Người dùng không có quyền xem ranking
            $allowedRoles = [
                UserRole::MANAGER->value,
                UserRole::DIRECTOR->value,
                UserRole::CEO->value,
            ];
            $this->validate(
                in_array($user->role->value, $allowedRoles),
                'Bạn không có quyền truy cập chức năng này.',
                403
            );

            // Lọc ngày công
            $year = $dto->year ?? (int) now()->year;
            $fromDate = null;
            $toDate = null;
            if ($dto->month) {
                $fromDate = \Carbon\Carbon::create($year, $dto->month, 1)->startOfMonth()->toDateString();
                $toDate = \Carbon\Carbon::create($year, $dto->month, 1)->endOfMonth()->toDateString();
            } elseif ($dto->quarter) {
                $startMonth = ($dto->quarter - 1) * 3 + 1;
                $fromDate = \Carbon\Carbon::create($year, $startMonth, 1)->startOfMonth()->toDateString();
                $toDate = \Carbon\Carbon::create($year, $startMonth + 2, 1)->endOfMonth()->toDateString();
            }

            // Lấy tất cả active employees trong phòng ban này
            $members = $this->authRepository->getActiveEmployeesByDepartment($departmentName);

            if ($members->isEmpty()) {
                return $this->success(null, 'Không tìm thấy dữ liệu phù hợp.');
            }

            $userIds = $members->pluck('id')->toArray();

            // Tính tổng quát các chỉ số
            $totalTransactions = $this->lotDepositRequestRepository->countCompletedTransactions($userIds, $fromDate, $toDate);

            $totalTours = $this->siteTourRepository->countSiteTours($userIds, $fromDate, $toDate);

            $totalMeetings = $this->customerMeetingRepository->countCustomerMeetings($userIds, $fromDate, $toDate);

            $totalReferrals = $this->referralHistoryRepository->countSuccessfulReferralsForUsers($userIds, $fromDate, $toDate);

            $workDaysMap = $this->attendanceRepository->countWorkDaysByUsers($userIds, $fromDate, $toDate);
            $absencesMap = $this->attendanceRepository->countFixedScheduleAbsencesByUsers($userIds, $fromDate, $toDate);
            $starsMap = $this->rewardPointHistoryRepository->sumStarsByUsersAndDateRange($userIds, $fromDate, $toDate);

            $rankedList = $members->map(function ($member) use ($workDaysMap, $absencesMap, $starsMap, $fromDate, $toDate) {
                $mId = (string) $member->id;

                $uTransactions = $this->lotDepositRequestRepository->countCompletedTransactions($mId, $fromDate, $toDate);

                $uTours = $this->siteTourRepository->countSiteTours($mId, $fromDate, $toDate);

                $uMeetings = $this->customerMeetingRepository->countCustomerMeetings($mId, $fromDate, $toDate);

                $uReferrals = $this->referralHistoryRepository->countSuccessfulReferralsForUsers($mId, $fromDate, $toDate);

                $uWorkDays = $workDaysMap->get($mId, 0);
                $uAbsences = $absencesMap->get($mId, 0);

                $kpiPoints = ($uTransactions * 10)
                    + ($uTours * 1)
                    + ($uMeetings * 0.5)
                    + ($uReferrals * 1)
                    + floor($uWorkDays / 5)
                    - ($uAbsences * 0.5);

                if ($fromDate || $toDate) {
                    $kpiStars = (int) $starsMap->get($mId, 0);
                } else {
                    $kpiStars = (int) ($member->employeeProfile->kpi_stars ?? 0);
                }

                return [
                    'id' => $mId,
                    'staff_code' => $member->staff_code,
                    'name' => $member->name,
                    'job_position' => $member->job_position,
                    'avatar' => $member->avatar,
                    'total_kpi_points' => $kpiPoints,
                    'successful_transactions' => $uTransactions,
                    'kpi_stars' => $kpiStars,
                ];
            });

            // Sắp xếp các nhân viên
            $sorted = $rankedList->sort(function ($a, $b) {
                if ($b['total_kpi_points'] <=> $a['total_kpi_points']) {
                    return $b['total_kpi_points'] <=> $a['total_kpi_points'];
                }
                if ($b['successful_transactions'] <=> $a['successful_transactions']) {
                    return $b['successful_transactions'] <=> $a['successful_transactions'];
                }
                return strcmp($a['name'], $b['name']);
            })->values();

            // Gán Rank
            $ranked = $sorted->map(function ($item, $index) {
                $item['rank'] = $index + 1;
                return $item;
            });

            $totalKpiPoints = $ranked->sum('total_kpi_points');
            $totalStars = $ranked->sum('kpi_stars');

            return $this->success([
                'department' => $departmentName,
                'kpi_summary' => [
                    'total_kpi_points' => $totalKpiPoints,
                    'total_transactions' => $totalTransactions,
                    'total_tours' => $totalTours,
                    'total_meetings' => $totalMeetings,
                    'total_referrals' => $totalReferrals,
                    'kpi_stars' => $totalStars,
                ],
                'employee_ranking' => $ranked,
            ], 'Tải chi tiết KPI phòng ban thành công.');
        });
    }
    /**
     * Cập nhật FCM Token của người dùng (Test case 5).
     *
     * @param UpdateFcmTokenDTO $dto
     * @return ServiceReturn
     */
    public function updateFcmToken(UpdateFcmTokenDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $user = $this->authRepository->findById($dto->userId);

            $this->validate(
                $user !== null,
                'Không tìm thấy thông tin tài khoản.',
                404
            );

            // Cập nhật fcm_token
            $this->authRepository->updateById($dto->userId, [
                'fcm_token' => $dto->fcmToken,
            ]);

            return $this->success(
                ['fcm_token' => $dto->fcmToken],
                'Cập nhật token thông báo thành công.'
            );
        });
    }
}
