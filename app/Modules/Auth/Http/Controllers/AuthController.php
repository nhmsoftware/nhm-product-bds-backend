<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Auth\DTO\RegisterDTO;
use App\Modules\Auth\Http\Requests\RegisterRequest;
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
        tags: ['Auth'],
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
    public function register(RegisterRequest $request): JsonResponse
    {
        $dto = RegisterDTO::fromRequest($request);
        $result = $this->authService->register($dto);

        return $this->resolveServiceReturn($result);
    }

    #[OA\Post(
        path: '/api/v1/auth/login',
        summary: 'Đăng nhập hệ thống (UC-02)',
        tags: ['Auth'],
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
    public function login(\App\Modules\Auth\Http\Requests\LoginRequest $request): JsonResponse
    {
        $dto = \App\Modules\Auth\DTO\LoginDTO::fromRequest($request);
        $result = $this->authService->login($dto);

        return $this->resolveServiceReturn($result);
    }

    #[OA\Post(
        path: '/api/v1/auth/forgot-password',
        summary: 'Yêu cầu quên mật khẩu - Gửi OTP (UC-03)',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['username'],
                properties: [
                    new OA\Property(property: 'username', type: 'string', example: 'nguyenvana@example.com'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Mã OTP đã được gửi.'),
            new OA\Response(response: 404, description: 'Tài khoản không tồn tại'),
            new OA\Response(response: 429, description: 'Vui lòng đợi 60 giây trước khi yêu cầu mã mới'),
        ]
    )]
    public function forgotPassword(\App\Modules\Auth\Http\Requests\ForgotPasswordRequest $request): JsonResponse
    {
        $dto = \App\Modules\Auth\DTO\ForgotPasswordDTO::fromRequest($request);
        $result = $this->authService->forgotPassword($dto);

        return $this->resolveServiceReturn($result);
    }

    #[OA\Post(
        path: '/api/v1/auth/verify-otp',
        summary: 'Xác thực mã OTP (UC-03)',
        tags: ['Auth'],
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
        responses: [
            new OA\Response(response: 200, description: 'Xác thực OTP thành công'),
            new OA\Response(response: 400, description: 'Mã OTP không hợp lệ hoặc đã hết hạn'),
        ]
    )]
    public function verifyOtp(\App\Modules\Auth\Http\Requests\VerifyOtpRequest $request): JsonResponse
    {
        $dto = \App\Modules\Auth\DTO\VerifyOtpDTO::fromRequest($request);
        $result = $this->authService->verifyOtp($dto);

        return $this->resolveServiceReturn($result);
    }

    #[OA\Post(
        path: '/api/v1/auth/reset-password',
        summary: 'Đặt lại mật khẩu mới (UC-03)',
        tags: ['Auth'],
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
        responses: [
            new OA\Response(response: 200, description: 'Đổi mật khẩu thành công'),
            new OA\Response(response: 400, description: 'Dữ liệu không hợp lệ hoặc mật khẩu trùng mật khẩu cũ'),
        ]
    )]
    public function resetPassword(\App\Modules\Auth\Http\Requests\ResetPasswordRequest $request): JsonResponse
    {
        $dto = \App\Modules\Auth\DTO\ResetPasswordDTO::fromRequest($request);
        $result = $this->authService->resetPassword($dto);

        return $this->resolveServiceReturn($result);
    }

    #[OA\Post(
        path: '/api/v1/auth/logout',
        summary: 'Đăng xuất hệ thống (UC-05)',
        tags: ['Auth'],
        security: [['bearerAuth' => []]],
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
    public function logout(): JsonResponse
    {
        $result = $this->authService->logout();

        return $this->resolveServiceReturn($result);
    }
}
