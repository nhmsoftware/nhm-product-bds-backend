<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Auth\DTO\ChangePasswordDTO;
use App\Modules\Auth\DTO\RegisterDTO;
use App\Modules\Auth\DTO\UpdateProfileDTO;
use App\Modules\Auth\DTO\UpdateEmployeeProfileDTO;
use App\Modules\Auth\DTO\UploadEmployeeDocumentDTO;
use App\Modules\Auth\Http\Requests\ChangePasswordRequest;
use App\Modules\Auth\Http\Requests\RegisterRequest;
use App\Modules\Auth\Http\Requests\UpdateProfileRequest;
use App\Modules\Auth\Http\Requests\UpdateEmployeeProfileRequest;
use App\Modules\Auth\Http\Requests\UploadEmployeeDocumentRequest;
use App\Modules\Auth\Http\Requests\UpdateFcmTokenRequest;
use App\Modules\Auth\DTO\UpdateFcmTokenDTO;
use App\Modules\Auth\Interfaces\AuthServiceInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class AuthController extends BaseController
{
    public function __construct(
        private readonly AuthServiceInterface $authService
    ) {
    }

    #[OA\Post(
        path: '/api/v1/auth/register',
        summary: 'Đăng ký tài khoản mới (UC-01)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'phone', 'password', 'agree_terms'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Nguyen Van A'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'nguyenvana@example.com'),
                    new OA\Property(property: 'phone', type: 'string', example: '0901234567'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'Password123@'),
                    new OA\Property(property: 'agree_terms', type: 'boolean', example: true),
                ]
            )
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Đăng ký thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Đăng ký thành công.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', example: 'uuid-string'),
                                new OA\Property(property: 'name', type: 'string', example: 'Nguyen Van A'),
                                new OA\Property(property: 'email', type: 'string', example: 'nguyenvana@example.com'),
                                new OA\Property(property: 'staff_code', type: 'string', example: 'ST-ABCXYZ'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ hoặc Email/SĐT đã tồn tại'),
        ]
    )]
    /**
     * Đăng ký tài khoản mới (UC-01).
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     * @throws \Throwable
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $dto = RegisterDTO::fromRequest($request);
        $result = $this->authService->register($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/auth/login',
        summary: 'Đăng nhập hệ thống (UC-02)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['username', 'password'],
                properties: [
                    new OA\Property(property: 'username', type: 'string', example: 'nguyenvana@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'Password123@'),
                    new OA\Property(property: 'remember', type: 'boolean', example: false),
                ]
            )
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Đăng nhập thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Đăng nhập thành công.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'access_token', type: 'string', example: 'jwt-token-string'),
                                new OA\Property(property: 'token_type', type: 'string', example: 'bearer'),
                                new OA\Property(property: 'expires_in', type: 'integer', example: 3600),
                                new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Sai mật khẩu'),
            new OA\Response(response: 403, description: 'Tài khoản bị khóa'),
            new OA\Response(response: 404, description: 'Tài khoản không tồn tại'),
        ]
    )]
    /**
     * Đăng nhập hệ thống (UC-02).
     *
     * @param \App\Modules\Auth\Http\Requests\LoginRequest $request
     * @return JsonResponse
     * @throws \Throwable
     */
    public function login(\App\Modules\Auth\Http\Requests\LoginRequest $request): JsonResponse
    {
        $dto = \App\Modules\Auth\DTO\LoginDTO::fromRequest($request);
        $result = $this->authService->login($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/auth/forgot-password',
        summary: 'Yêu cầu quên mật khẩu - Gửi OTP (UC-03)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['username'],
                properties: [
                    new OA\Property(property: 'username', type: 'string', example: 'nguyenvana@example.com'),
                ]
            )
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Mã OTP đã được gửi.'),
            new OA\Response(response: 404, description: 'Tài khoản không tồn tại'),
            new OA\Response(response: 429, description: 'Vui lòng đợi 60 giây trước khi yêu cầu mã mới'),
        ]
    )]
    /**
     * Yêu cầu quên mật khẩu (UC-03).
     *
     * @param \App\Modules\Auth\Http\Requests\ForgotPasswordRequest $request
     * @return JsonResponse
     * @throws \Throwable
     */
    public function forgotPassword(\App\Modules\Auth\Http\Requests\ForgotPasswordRequest $request): JsonResponse
    {
        $dto = \App\Modules\Auth\DTO\ForgotPasswordDTO::fromRequest($request);
        $result = $this->authService->forgotPassword($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/auth/verify-otp',
        summary: 'Xác thực mã OTP (UC-03)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['username', 'otp'],
                properties: [
                    new OA\Property(property: 'username', type: 'string', example: 'nguyenvana@example.com'),
                    new OA\Property(property: 'otp', type: 'string', example: '123456'),
                ]
            )
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Xác thực OTP thành công'),
            new OA\Response(response: 400, description: 'Mã OTP không hợp lệ hoặc đã hết hạn'),
        ]
    )]
    /**
     * Xác thực mã OTP (UC-03).
     *
     * @param \App\Modules\Auth\Http\Requests\VerifyOtpRequest $request
     * @return JsonResponse
     * @throws \Throwable
     */
    public function verifyOtp(\App\Modules\Auth\Http\Requests\VerifyOtpRequest $request): JsonResponse
    {
        $dto = \App\Modules\Auth\DTO\VerifyOtpDTO::fromRequest($request);
        $result = $this->authService->verifyOtp($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/auth/reset-password',
        summary: 'Đặt lại mật khẩu mới (UC-03)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['username', 'otp', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'username', type: 'string', example: 'nguyenvana@example.com'),
                    new OA\Property(property: 'otp', type: 'string', example: '123456'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'NewPassword123@'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'NewPassword123@'),
                ]
            )
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Đổi mật khẩu thành công'),
            new OA\Response(response: 400, description: 'Dữ liệu không hợp lệ hoặc mật khẩu trùng mật khẩu cũ'),
        ]
    )]
    /**
     * Đặt lại mật khẩu mới (UC-03).
     *
     * @param \App\Modules\Auth\Http\Requests\ResetPasswordRequest $request
     * @return JsonResponse
     * @throws \Throwable
     */
    public function resetPassword(\App\Modules\Auth\Http\Requests\ResetPasswordRequest $request): JsonResponse
    {
        $dto = \App\Modules\Auth\DTO\ResetPasswordDTO::fromRequest($request);
        $result = $this->authService->resetPassword($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/auth/logout',
        summary: 'Đăng xuất hệ thống (UC-05)',
        security: [['bearerAuth' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Đăng xuất thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Đăng xuất thành công.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập hoặc phiên đã hết hạn'),
        ]
    )]
    /**
     * Đăng xuất hệ thống (UC-05).
     *
     * @return JsonResponse
     * @throws \Throwable
     */
    public function logout(): JsonResponse
    {
        $result = $this->authService->logout();

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/auth/profile',
        description: 'Cho phép khách hàng xem thông tin cá nhân cơ bản của tài khoản bao gồm họ tên, số điện thoại, email và địa chỉ nếu có.',
        summary: 'Xem hồ sơ cá nhân của khách hàng (UC-030)',
        security: [['bearerAuth' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải thông tin cá nhân thành công.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'uuid-string'),
                                new OA\Property(property: 'staff_code', type: 'string', example: 'ST-ABCXYZ'),
                                new OA\Property(property: 'name', type: 'string', example: 'Nguyen Van A'),
                                new OA\Property(property: 'cccd', type: 'string', nullable: true, example: '037123456789'),
                                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'nguyenvana@example.com'),
                                new OA\Property(property: 'phone', type: 'string', example: '0901234567'),
                                new OA\Property(property: 'address', type: 'string', example: '123 Đường ABC, Quận 1, TP. HCM'),
                                new OA\Property(property: 'avatar', type: 'string', nullable: true, example: null),
                                new OA\Property(property: 'role', type: 'integer', example: \App\Modules\Auth\Models\Enums\UserRole::BUYER->value),
                                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-05-18T15:40:00+07:00'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Chưa đăng nhập hoặc phiên đã hết hạn'
            ),
            new OA\Response(
                response: 403,
                description: 'Tài khoản đã bị khóa (A2)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Tài khoản của bạn đã bị khóa.'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Không tải được thông tin cá nhân (A1)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Không thể tải thông tin cá nhân. Vui lòng thử lại.'),
                    ]
                )
            ),
        ]
    )]
    public function profile(): JsonResponse
    {
        $userId = auth('api')->id();
        $result = $this->authService->getProfile($userId);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Put(
        path: '/api/v1/auth/profile',
        description: 'Cho phép khách hàng chỉnh sửa và cập nhật thông tin cá nhân cơ bản như họ tên, số điện thoại, email và địa chỉ trên hệ thống.',
        summary: 'Cập nhật hồ sơ cá nhân của khách hàng (UC-031)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'phone'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Nguyen Van A'),
                    new OA\Property(property: 'cccd', type: 'string', nullable: true, example: '037123456789'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'nguyenvana@example.com'),
                    new OA\Property(property: 'phone', type: 'string', example: '0901234567'),
                    new OA\Property(property: 'address', type: 'string', example: '123 Đường ABC, Quận 1, TP. HCM'),
                ]
            )
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Cập nhật thông tin thành công.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'uuid-string'),
                                new OA\Property(property: 'staff_code', type: 'string', example: 'ST-ABCXYZ'),
                                new OA\Property(property: 'name', type: 'string', example: 'Nguyen Van A'),
                                new OA\Property(property: 'cccd', type: 'string', nullable: true, example: '037123456789'),
                                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'nguyenvana@example.com'),
                                new OA\Property(property: 'phone', type: 'string', example: '0901234567'),
                                new OA\Property(property: 'address', type: 'string', example: '123 Đường ABC, Quận 1, TP. HCM'),
                                new OA\Property(property: 'avatar', type: 'string', nullable: true, example: null),
                                new OA\Property(property: 'role', type: 'integer', example: \App\Modules\Auth\Models\Enums\UserRole::BUYER->value),
                                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-05-18T15:40:00+07:00'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Dữ liệu không hợp lệ (A1, A2, A3, A4)'
            ),
            new OA\Response(
                response: 401,
                description: 'Chưa đăng nhập hoặc phiên đã hết hạn'
            ),
            new OA\Response(
                response: 403,
                description: 'Tài khoản đã bị khóa'
            ),
            new OA\Response(
                response: 500,
                description: 'Lỗi cập nhật dữ liệu (A5)'
            ),
        ]
    )]
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $dto = UpdateProfileDTO::fromRequest($request);
        $result = $this->authService->updateProfile($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Put(
        path: '/api/v1/auth/change-password',
        description: 'Cho phép người dùng thay đổi mật khẩu tài khoản nhằm tăng cường bảo mật cho hệ thống.',
        summary: 'Thay đổi mật khẩu tài khoản (UC-032)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['current_password', 'new_password', 'new_password_confirmation'],
                properties: [
                    new OA\Property(property: 'current_password', type: 'string', format: 'password', example: 'OldPassword123@'),
                    new OA\Property(property: 'new_password', type: 'string', format: 'password', example: 'NewPassword123@'),
                    new OA\Property(property: 'new_password_confirmation', type: 'string', format: 'password', example: 'NewPassword123@'),
                ]
            )
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Đổi mật khẩu thành công.'),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Dữ liệu không hợp lệ hoặc mật khẩu hiện tại không đúng (A1, A2, A3, A4, A5)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Mật khẩu hiện tại không chính xác.'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Chưa đăng nhập hoặc phiên đã hết hạn'
            ),
            new OA\Response(
                response: 403,
                description: 'Tài khoản đã bị khóa'
            ),
            new OA\Response(
                response: 500,
                description: 'Lỗi hệ thống hoặc lỗi lưu mật khẩu mới (A6)'
            ),
        ]
    )]
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $dto = ChangePasswordDTO::fromRequest($request);
        $result = $this->authService->changePassword($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        // Invalidate current JWT token to enforce re-login
        auth('api')->logout();

        return $this->sendSuccess(null, $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/auth/employee-profile',
        description: 'Cho phép nhân sự xem hồ sơ cá nhân bao gồm thông tin cá nhân, thông tin ngân hàng, trình độ học vấn, kinh nghiệm làm việc và tài liệu đính kèm trên hệ thống.',
        summary: 'Xem hồ sơ cá nhân nhân sự (UC-033)',
        security: [['bearerAuth' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải hồ sơ nhân viên thành công.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(
                                    property: 'user',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '123e4567-e89b-12d3-a456-426614174000'),
                                        new OA\Property(property: 'name', type: 'string', example: 'Nguyễn Văn A'),
                                        new OA\Property(property: 'cccd', type: 'string', nullable: true, example: '037123456789'),
                                        new OA\Property(property: 'phone', type: 'string', example: '0901234567'),
                                        new OA\Property(property: 'email', type: 'string', example: 'staff@example.com'),
                                        new OA\Property(property: 'avatar', type: 'string', nullable: true),
                                        new OA\Property(property: 'role', type: 'integer', example: \App\Modules\Auth\Models\Enums\UserRole::EMPLOYEE->value),
                                        new OA\Property(property: 'address', type: 'string', example: '123 Đường ABC, Quận 1, TP. HCM'),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'employee_details',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'employee_title', type: 'string', example: 'Nhân viên xuất sắc năm 2026'),
                                        new OA\Property(property: 'identity_card', type: 'string', example: '037123456789'),
                                        new OA\Property(property: 'dob', type: 'string', format: 'date', example: '1995-10-15'),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'bank_info',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'bank_account_name', type: 'string', example: 'NGUYEN VAN A'),
                                        new OA\Property(property: 'bank_account_number', type: 'string', example: '190345678910'),
                                        new OA\Property(property: 'bank_name', type: 'string', example: 'Techcombank'),
                                        new OA\Property(property: 'status_message', type: 'string', nullable: true, example: 'Chưa cập nhật thông tin ngân hàng.'),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'education_experience',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'education', type: 'string', example: 'Đại học Bách Khoa TP.HCM'),
                                        new OA\Property(property: 'major', type: 'string', example: 'Công nghệ thông tin'),
                                        new OA\Property(property: 'experience', type: 'string', example: '3 năm kinh nghiệm lập trình Laravel'),
                                        new OA\Property(property: 'status_message', type: 'string', nullable: true, example: 'Chưa cập nhật thông tin học vấn hoặc kinh nghiệm.'),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'attachments',
                                    properties: [
                                        new OA\Property(
                                            property: 'list',
                                            type: 'array',
                                            items: new OA\Items(
                                                properties: [
                                                    new OA\Property(property: 'type', type: 'string', example: 'labor_contract'),
                                                    new OA\Property(property: 'name', type: 'string', example: 'Hop_Dong_Lao_Dong.pdf'),
                                                    new OA\Property(property: 'url', type: 'string', example: 'https://bds-app.s3.amazonaws.com/contracts/hopdong.pdf'),
                                                ],
                                                type: 'object'
                                            )
                                        ),
                                        new OA\Property(property: 'status_message', type: 'string', nullable: true, example: 'Chưa có tài liệu đính kèm.'),
                                    ],
                                    type: 'object'
                                ),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Chưa đăng nhập hoặc phiên đã hết hạn'
            ),
            new OA\Response(
                response: 403,
                description: 'Tài khoản đã bị khóa'
            ),
            new OA\Response(
                response: 404,
                description: 'Không thể tải thông tin hồ sơ (A1)'
            ),
        ]
    )]
    public function employeeProfile(): JsonResponse
    {
        $userId = (string) auth('api')->id();
        $result = $this->authService->getEmployeeProfile($userId);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Put(
        path: '/api/v1/auth/employee-profile',
        description: 'Cho phép nhân sự chỉnh sửa và cập nhật thông tin hồ sơ cá nhân trên hệ thống, bao gồm thông tin liên hệ, địa chỉ, thông tin ngân hàng, học vấn, kinh nghiệm.',
        summary: 'Cập nhật hồ sơ cá nhân nhân sự (UC-034)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Nguyễn Văn B'),
                    new OA\Property(property: 'cccd', type: 'string', nullable: true, example: '037123456789'),
                    new OA\Property(property: 'phone', type: 'string', example: '0987654321'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'staff@example.com'),
                    new OA\Property(property: 'avatar', type: 'string', nullable: true, example: 'https://example.com/avatar.png'),
                    new OA\Property(property: 'dob', type: 'string', format: 'date', nullable: true, example: '1995-10-15'),
                    new OA\Property(property: 'address', type: 'string', nullable: true, example: '123 Đường XYZ, Quận 3, TP. HCM'),
                    new OA\Property(property: 'bank_account_name', type: 'string', nullable: true, example: 'NGUYEN VAN B'),
                    new OA\Property(property: 'bank_account_number', type: 'string', nullable: true, example: '190345678910'),
                    new OA\Property(property: 'bank_name', type: 'string', nullable: true, example: 'Techcombank'),
                    new OA\Property(property: 'education', type: 'string', nullable: true, example: 'Đại học Bách Khoa TP.HCM'),
                    new OA\Property(property: 'major', type: 'string', nullable: true, example: 'Công nghệ thông tin'),
                    new OA\Property(property: 'experience', type: 'string', nullable: true, example: '3 năm kinh nghiệm lập trình Laravel'),
                    new OA\Property(property: 'employee_title', type: 'string', nullable: true, example: 'Nhân viên xuất sắc năm 2026'),
                ]
            )
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Cập nhật hồ sơ thành công.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(
                                    property: 'user',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '123e4567-e89b-12d3-a456-426614174000'),
                                        new OA\Property(property: 'name', type: 'string', example: 'Nguyễn Văn B'),
                                        new OA\Property(property: 'cccd', type: 'string', nullable: true, example: '037123456789'),
                                        new OA\Property(property: 'phone', type: 'string', example: '0987654321'),
                                        new OA\Property(property: 'email', type: 'string', example: 'staff@example.com'),
                                        new OA\Property(property: 'avatar', type: 'string', nullable: true),
                                        new OA\Property(property: 'role', type: 'integer', example: \App\Modules\Auth\Models\Enums\UserRole::EMPLOYEE->value),
                                        new OA\Property(property: 'address', type: 'string', example: '123 Đường XYZ, Quận 3, TP. HCM'),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'employee_details',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'employee_title', type: 'string', example: 'Nhân viên xuất sắc năm 2026'),
                                        new OA\Property(property: 'identity_card', type: 'string', example: '037123456789'),
                                        new OA\Property(property: 'dob', type: 'string', format: 'date', example: '1995-10-15'),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'bank_info',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'bank_account_name', type: 'string', example: 'NGUYEN VAN B'),
                                        new OA\Property(property: 'bank_account_number', type: 'string', example: '190345678910'),
                                        new OA\Property(property: 'bank_name', type: 'string', example: 'Techcombank'),
                                        new OA\Property(property: 'status_message', type: 'string', nullable: true),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'education_experience',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'education', type: 'string', example: 'Đại học Bách Khoa TP.HCM'),
                                        new OA\Property(property: 'major', type: 'string', example: 'Công nghệ thông tin'),
                                        new OA\Property(property: 'experience', type: 'string', example: '3 năm kinh nghiệm lập trình Laravel'),
                                        new OA\Property(property: 'status_message', type: 'string', nullable: true),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'attachments',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'list', type: 'array', items: new OA\Items(type: 'object')),
                                        new OA\Property(property: 'status_message', type: 'string', nullable: true),
                                    ]
                                ),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Chưa đăng nhập hoặc phiên đã hết hạn'
            ),
            new OA\Response(
                response: 403,
                description: 'Tài khoản đã bị khóa'
            ),
            new OA\Response(
                response: 422,
                description: 'Dữ liệu không hợp lệ (A1, A2, A3, A4)'
            ),
            new OA\Response(
                response: 500,
                description: 'Lỗi cập nhật hồ sơ (A5)'
            ),
        ]
    )]
    public function updateEmployeeProfile(UpdateEmployeeProfileRequest $request): JsonResponse
    {
        $dto = UpdateEmployeeProfileDTO::fromRequest($request);
        $result = $this->authService->updateEmployeeProfile($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/auth/employee-profile/documents',
        description: 'Cho phép nhân sự tải lên các tài liệu cá nhân hoặc hồ sơ liên quan đến công việc như hợp đồng lao động, bằng cấp, chứng chỉ, CCCD/CMND và các tài liệu bổ sung khác.',
        summary: 'Tải tài liệu nhân sự (UC-035)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['type', 'file'],
                    properties: [
                        new OA\Property(
                            property: 'type',
                            type: 'string',
                            description: 'Loại tài liệu: Hợp đồng lao động, Bằng cấp, Chứng chỉ, CCCD/CMND, Tài liệu khác',
                            example: 'Hợp đồng lao động'
                        ),
                        new OA\Property(
                            property: 'file',
                            type: 'string',
                            format: 'binary',
                            description: 'File tài liệu (pdf, doc, docx, jpg, jpeg, png)'
                        ),
                    ]
                )
            )
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải tài liệu thành công.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(
                                    property: 'document',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'type', type: 'string', example: 'Hợp đồng lao động'),
                                        new OA\Property(property: 'name', type: 'string', example: 'hop-dong.pdf'),
                                        new OA\Property(property: 'url', type: 'string', example: '/storage/employee_documents/xyz.pdf'),
                                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'list',
                                    type: 'array',
                                    items: new OA\Items(type: 'object')
                                ),
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Chưa đăng nhập'
            ),
            new OA\Response(
                response: 403,
                description: 'Tài khoản đã bị khóa'
            ),
            new OA\Response(
                response: 404,
                description: 'Lỗi tải tài liệu / User không tồn tại'
            ),
            new OA\Response(
                response: 422,
                description: 'Dữ liệu không hợp lệ (A1, A2, A3, A4)'
            ),
            new OA\Response(
                response: 500,
                description: 'Lỗi máy chủ (A5)'
            ),
        ]
    )]
    public function uploadEmployeeDocument(UploadEmployeeDocumentRequest $request): JsonResponse
    {
        $dto = UploadEmployeeDocumentDTO::fromRequest($request);
        $result = $this->authService->uploadEmployeeDocument($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Put(
        path: '/api/v1/auth/fcm-token',
        description: 'Cập nhật FCM Token của người dùng (dùng cho Push Notification).',
        summary: 'Cập nhật FCM Token',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['fcm_token'],
                properties: [
                    new OA\Property(property: 'fcm_token', type: 'string', example: 'ExponentPushToken[xxx]'),
                ]
            )
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cập nhật thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'fcm_token', type: 'string', example: 'ExponentPushToken[xxx]'),
                            ],
                            type: 'object'
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'Cập nhật token thông báo thành công.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập hoặc phiên đã hết hạn'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    /**
     * Cập nhật FCM Token.
     *
     * @param UpdateFcmTokenRequest $request
     * @return JsonResponse
     */
    public function updateFcmToken(UpdateFcmTokenRequest $request): JsonResponse
    {
        $dto = UpdateFcmTokenDTO::fromRequest($request);
        $result = $this->authService->updateFcmToken($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}

