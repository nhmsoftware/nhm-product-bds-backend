<?php

namespace App\Modules\Auth\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Auth\DTO\ForgotPasswordDTO;
use App\Modules\Auth\DTO\RegisterDTO;
use App\Modules\Auth\DTO\ResetPasswordDTO;
use App\Modules\Auth\DTO\VerifyOtpDTO;
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
            $data['role']       = 'buyer'; // Mặc định là buyer khi đăng ký qua public form
            $data['is_active']  = true;

            // 3. Tạo user
            $user = $this->authRepository->create($data);

            // 4. Bắn Event
            event(new UserRegistered($user));

            return $this->success($user, 'Đăng ký thành công.');
        }, useTransaction: true);
    }

    /**
     * Đăng nhập hệ thống và cấp JWT token (UC-02).
     * 
     * @param \App\Modules\Auth\DTO\LoginDTO $dto
     * @return ServiceReturn
     * @throws \Throwable
     */
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

            return $this->success(null, 'Mã OTP đã được gửi.');
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

    /**
     * Cập nhật thông tin hồ sơ cá nhân của người dùng (UC-031).
     * 
     * @param \App\Modules\Auth\DTO\UpdateProfileDTO $dto
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function updateProfile(\App\Modules\Auth\DTO\UpdateProfileDTO $dto): ServiceReturn
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

            $profileData = [
                'user' => [
                    'id'             => (string) $user->id,
                    'name'           => $user->name,
                    'phone'          => $user->phone ?: 'Chưa cập nhật.',
                    'email'          => $user->email,
                    'avatar'         => $user->avatar,
                    'role'           => $user->role->serialize(), // Chức vụ
                    'address'        => $user->address ?: 'Chưa cập nhật.',
                ],
                'employee_details' => [
                    'employee_title'  => $ep?->employee_title ?: 'Chưa cập nhật.',
                    'identity_card'   => $ep?->identity_card ?: 'Chưa cập nhật.',
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
            $userUpdated = $this->authRepository->updateById($dto->userId, [
                'name'    => $dto->name,
                'phone'   => $dto->phone,
                'email'   => $dto->email,
                'avatar'  => $dto->avatar,
                'address' => $dto->address,
            ]);

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

            $profileData = [
                'user' => [
                    'id'             => (string) $freshUser->id,
                    'name'           => $freshUser->name,
                    'phone'          => $freshUser->phone ?: 'Chưa cập nhật.',
                    'email'          => $freshUser->email,
                    'avatar'         => $freshUser->avatar,
                    'role'           => $freshUser->role->serialize(),
                    'address'        => $freshUser->address ?: 'Chưa cập nhật.',
                ],
                'employee_details' => [
                    'employee_title'  => $ep?->employee_title ?: 'Chưa cập nhật.',
                    'identity_card'   => $ep?->identity_card ?: 'Chưa cập nhật.',
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
     * Helper tìm user qua email hoặc phone.
     * 
     * @param string $username
     * @return \App\Modules\Auth\Models\User|null
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
}
