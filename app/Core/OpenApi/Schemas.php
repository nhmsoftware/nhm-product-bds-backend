<?php

declare(strict_types=1);

namespace App\Core\OpenApi;

use OpenApi\Attributes as OA;

/**
 * OpenAPI Schema Components
 *
 * Định nghĩa các schema dùng chung cho API documentation.
 */
class Schemas
{
    /**
     * Profile Response Schema
     * Schema cho response thông tin hồ sơ người dùng
     */
    #[OA\Schema(
        schema: 'ProfileResponse',
        description: 'Thông tin hồ sơ người dùng',
        required: ['id', 'role', 'phone', 'is_verified', 'is_phone_verified'],
        properties: [
            new OA\Property(property: 'id', type: 'string', example: '1', description: 'ID người dùng'),
            new OA\Property(property: 'role', type: 'integer', enum: [1, 2, 3], example: 2, description: 'Vai trò: 1=Driver, 2=Customer, 3=Merchant'),
            new OA\Property(property: 'role_label', type: 'string', example: 'Khách hàng', description: 'Tên vai trò'),
            new OA\Property(
                property: 'avatar',
                description: 'Ảnh đại diện',
                properties: [
                    new OA\Property(property: 'value', type: 'string', nullable: true, example: 'https://example.com/avatar.jpg'),
                    new OA\Property(property: 'display', type: 'string', example: 'Chưa cập nhật'),
                    new OA\Property(property: 'field', type: 'string', example: 'avatar')
                ],
                type: 'object'
            ),
            new OA\Property(
                property: 'full_name',
                properties: [
                    new OA\Property(property: 'value', type: 'string', nullable: true, example: 'Nguyễn Văn A'),
                    new OA\Property(property: 'display', type: 'string', example: 'Nguyễn Văn A'),
                    new OA\Property(property: 'field', type: 'string', example: 'full_name')
                ],
                type: 'object'
            ),
            new OA\Property(property: 'phone', type: 'string', example: '0912345678', description: 'Số điện thoại'),
            new OA\Property(
                property: 'email',
                properties: [
                    new OA\Property(property: 'value', type: 'string', nullable: true, example: 'user@example.com'),
                    new OA\Property(property: 'display', type: 'string', example: 'Chưa cập nhật'),
                    new OA\Property(property: 'field', type: 'string', example: 'email')
                ],
                type: 'object'
            ),
            new OA\Property(
                property: 'gender',
                properties: [
                    new OA\Property(property: 'value', type: 'integer', nullable: true, enum: [1, 2, 3], example: 1),
                    new OA\Property(property: 'display', type: 'string', example: 'Nam'),
                    new OA\Property(property: 'field', type: 'string', example: 'gender')
                ],
                type: 'object'
            ),
            new OA\Property(property: 'gender_label', type: 'string', nullable: true, example: 'Nam'),
            new OA\Property(
                property: 'address',
                properties: [
                    new OA\Property(property: 'value', type: 'string', nullable: true, example: '123 Đường ABC, Quận 1, TP.HCM'),
                    new OA\Property(property: 'display', type: 'string', example: 'Chưa cập nhật'),
                    new OA\Property(property: 'field', type: 'string', example: 'address')
                ],
                type: 'object'
            ),
            new OA\Property(
                property: 'citizen_id',
                properties: [
                    new OA\Property(property: 'value', type: 'string', nullable: true, example: '123456789012'),
                    new OA\Property(property: 'display', type: 'string', example: 'Chưa cập nhật'),
                    new OA\Property(property: 'field', type: 'string', example: 'citizen_id')
                ],
                type: 'object'
            ),
            new OA\Property(property: 'is_verified', type: 'boolean', example: true, description: 'Đã xác thực tài khoản'),
            new OA\Property(property: 'is_phone_verified', type: 'boolean', example: true, description: 'Đã xác thực số điện thoại'),
            new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true, example: '2024-01-15T10:30:00Z'),
            new OA\Property(
                property: 'customer_specific',
                description: 'Thông tin riêng cho Customer',
                properties: [
                    new OA\Property(property: 'birthday', type: 'string', nullable: true, example: '1990-01-15')
                ],
                type: 'object',
                nullable: true
            ),
            new OA\Property(
                property: 'driver_specific',
                description: 'Thông tin riêng cho Driver',
                type: 'object',
                nullable: true
            ),
            new OA\Property(
                property: 'merchant_specific',
                description: 'Thông tin riêng cho Merchant',
                type: 'object',
                nullable: true
            )
        ]
    )]
    public static function profileResponse(): void
    {
        // Schema definition only
    }

    /**
     * Saved Address Response Schema
     * Schema cho response địa chỉ đã lưu
     */
    #[OA\Schema(
        schema: 'SavedAddressResponse',
        description: 'Thông tin địa chỉ đã lưu',
        required: ['id', 'label', 'name', 'address_text', 'lat', 'lng', 'receiver_name', 'receiver_phone', 'is_default'],
        properties: [
            new OA\Property(property: 'id', type: 'string', example: '1', description: 'ID địa chỉ'),
            new OA\Property(property: 'label', type: 'integer', enum: [1, 2, 3, 4], example: 1, description: 'Nhãn: 1=Nhà, 2=Công ty, 3=Nhà hàng yêu thích, 4=Khác'),
            new OA\Property(property: 'label_text', type: 'string', example: 'Nhà', description: 'Tên nhãn'),
            new OA\Property(property: 'name', type: 'string', maxLength: 200, example: 'Nhà A', description: 'Tên gợi nhớ'),
            new OA\Property(property: 'address_text', type: 'string', maxLength: 500, example: '123 Đường ABC, Phường 5, Quận 1, TP.HCM', description: 'Địa chỉ đầy đủ'),
            new OA\Property(property: 'lat', type: 'number', format: 'double', example: 10.7629),
            new OA\Property(property: 'lng', type: 'number', format: 'double', example: 106.6818),
            new OA\Property(property: 'receiver_name', description: 'Tên người nhận', type: 'string', example: 'Nguyễn Văn A', maxLength: 100),
            new OA\Property(property: 'receiver_phone', type: 'string', example: '0912345678:', maxLength: 20),
            new OA\Property(property: 'note', description: 'Ghi chú', type: 'string', example: 'Gần siêu thị', nullable: true, maxLength: 500),
            new OA\Property(property: 'is_default', description: 'Là địa chỉ mặc định', type: 'boolean', example: true),
            new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-01-15T10:30:00Z'),
            new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2024-01-15T10:30:00Z')
        ]
    )]
    public static function savedAddressResponse(): void
    {
        // Schema definition only
    }

    /**
     * OTP Required Response Schema
     * Schema cho response yêu cầu xác thực OTP
     */
    #[OA\Schema(
        schema: 'OtpRequiredResponse',
        description: 'Response yêu cầu xác thực OTP',
        required: ['success', 'data', 'message'],
        properties: [
            new OA\Property(property: 'success', type: 'boolean', example: true),
            new OA\Property(
                property: 'data',
                properties: [
                    new OA\Property(property: 'requires_otp', type: 'boolean', example: true),
                    new OA\Property(property: 'sensitive_fields', type: 'array', items: new OA\Items(type: 'string'), example: ['phone', 'email'])
                ],
                type: 'object'
            ),
            new OA\Property(property: 'message', type: 'string', example: 'Có thay đổi thông tin nhạy cảm. Vui lòng xác thực OTP.')
        ]
    )]
    public static function otpRequiredResponse(): void
    {
        // Schema definition only
    }

    /**
     * Error Response Schema
     * Schema cho response lỗi
     */
    #[OA\Schema(
        schema: 'ErrorResponse',
        description: 'Response lỗi',
        required: ['success', 'message'],
        properties: [
            new OA\Property(property: 'success', type: 'boolean', example: false),
            new OA\Property(property: 'message', type: 'string', example: 'Có lỗi xảy ra.'),
            new OA\Property(
                property: 'errors',
                description: 'Danh sách lỗi validation',
                type: 'object',
                nullable: true,
                additionalProperties: new OA\AdditionalProperties(type: 'array', items: new OA\Items(type: 'string'))
            )
        ]
    )]
    public static function errorResponse(): void
    {
        // Schema definition only
    }

    /**
     * Forbidden Response Schema
     */
    #[OA\Schema(
        schema: 'ForbiddenResponse',
        description: 'Lỗi không có quyền truy cập hoặc tài khoản bị khóa',
        required: ['success', 'message'],
        properties: [
            new OA\Property(property: 'success', type: 'boolean', example: false),
            new OA\Property(property: 'message', type: 'string', example: 'Tài khoản của bạn đã bị khóa hoặc bạn không có quyền thực hiện hành động này.')
        ]
    )]
    public static function forbiddenResponse(): void {}

    /**
     * Validation Error Response Schema
     */
    #[OA\Schema(
        schema: 'ValidationErrorResponse',
        description: 'Lỗi dữ liệu đầu vào không hợp lệ',
        required: ['success', 'message', 'errors'],
        properties: [
            new OA\Property(property: 'success', type: 'boolean', example: false),
            new OA\Property(property: 'message', type: 'string', example: 'Dữ liệu không hợp lệ.'),
            new OA\Property(
                property: 'errors',
                type: 'object',
                additionalProperties: new OA\AdditionalProperties(type: 'array', items: new OA\Items(type: 'string'))
            )
        ]
    )]
    public static function validationErrorResponse(): void {}

    /**
     * Server Error Response Schema
     */
    #[OA\Schema(
        schema: 'ServerErrorResponse',
        description: 'Lỗi server nội bộ',
        required: ['success', 'message'],
        properties: [
            new OA\Property(property: 'success', type: 'boolean', example: false),
            new OA\Property(property: 'message', type: 'string', example: 'Có lỗi xảy ra trên hệ thống. Vui lòng thử lại sau.')
        ]
    )]
    public static function serverErrorResponse(): void {}

    /**
     * Unauthorized Response Schema
     */
    #[OA\Schema(
        schema: 'UnauthorizedResponse',
        description: 'Lỗi chưa đăng nhập hoặc token hết hạn',
        required: ['success', 'message'],
        properties: [
            new OA\Property(property: 'success', type: 'boolean', example: false),
            new OA\Property(property: 'message', type: 'string', example: 'Vui lòng đăng nhập để thực hiện hành động này.')
        ]
    )]
    public static function unauthorizedResponse(): void {}

    /**
     * Verify OTP Profile Request Schema
     */
    #[OA\Schema(
        schema: 'VerifyOtpProfileRequest',
        description: 'Request body để xác thực OTP khi thay đổi thông tin nhạy cảm',
        required: ['otp'],
        properties: [
            new OA\Property(property: 'otp', description: 'Mã OTP gồm 6 chữ số', type: 'string', example: '123456'),
            new OA\Property(
                property: 'sensitive_data',
                title: 'Dữ liệu nhạy cảm cần cập nhật',
                description: 'Dữ liệu nhạy cảm cần cập nhật (ví dụ: phone hoặc email)',
                type: 'object',
                nullable: true
            )
        ]
    )]
    public static function verifyOtpProfileRequest(): void {}

    /**
     * Change Password Request Schema
     */
    #[OA\Schema(
        schema: 'ChangePasswordRequest',
        description: 'Request body để thay đổi mật khẩu',
        required: ['current_password', 'new_password', 'new_password_confirmation'],
        properties: [
            new OA\Property(property: 'current_password', type: 'string', example: 'OldPassword123!', description: 'Mật khẩu hiện tại'),
            new OA\Property(property: 'new_password', type: 'string', example: 'NewPassword123!', description: 'Mật khẩu mới (tối thiểu 8 ký tự, bao gồm chữ và số)'),
            new OA\Property(property: 'new_password_confirmation', type: 'string', example: 'NewPassword123!', description: 'Xác nhận mật khẩu mới')
        ]
    )]
    /**
     * Voucher Response Schema
     */
    #[OA\Schema(
        schema: 'VoucherResponse',
        description: 'Thông tin chi tiết mã giảm giá',
        required: ['id', 'code', 'service_type', 'discount_type', 'discount_value'],
        properties: [
            new OA\Property(property: 'id', type: 'string', example: '1'),
            new OA\Property(property: 'code', type: 'string', example: 'DEMO10'),
            new OA\Property(
                property: 'service_type', 
                description: 'Loại dịch vụ áp dụng. 1: Chuyến xe, 2: Đồ ăn, 3: Đa dịch vụ, 4: Giao hàng, 5: Tất cả', 
                type: 'integer', 
                example: 1
            ),
            new OA\Property(
                property: 'discount_type', 
                description: 'Loại giảm giá. 1: Giảm tiền mặt cố định, 2: Giảm theo phần trăm', 
                type: 'integer', 
                example: 1
            ),
            new OA\Property(property: 'discount_value', type: 'number', format: 'float', example: 10000),
            new OA\Property(property: 'min_order_amount', type: 'number', format: 'float', example: 50000),
            new OA\Property(property: 'max_discount_amount', type: 'number', format: 'float', nullable: true, example: 50000),
            new OA\Property(property: 'valid_until', type: 'string', format: 'date-time'),
            new OA\Property(property: 'description', type: 'string', nullable: true),
            new OA\Property(property: 'is_saved', type: 'boolean', example: false, description: 'Đã lưu vào ví hay chưa'),
            new OA\Property(property: 'status', type: 'string', example: 'Available', description: 'Trạng thái: Available, Expired, Used'),
        ]
    )]
    public static function voucherResponse(): void {}

    /**
     * Reward Transaction Response Schema
     */
    #[OA\Schema(
        schema: 'RewardTransactionResponse',
        description: 'Lịch sử giao dịch điểm thưởng',
        required: ['id', 'type', 'amount'],
        properties: [
            new OA\Property(property: 'id', type: 'string', example: '1'),
            new OA\Property(
                property: 'type', 
                description: 'Loại giao dịch. 1: Tích điểm (Earn), 2: Sử dụng điểm (Redeem), 3: Điểm hết hạn (Expire)', 
                type: 'integer', 
                example: 1
            ),
            new OA\Property(property: 'type_label', type: 'string', example: 'Tích điểm'),
            new OA\Property(property: 'amount', type: 'integer', example: 100),
            new OA\Property(property: 'balance_before', type: 'integer', example: 500),
            new OA\Property(property: 'balance_after', type: 'integer', example: 600),
            new OA\Property(property: 'note', type: 'string', nullable: true),
            new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        ]
    )]
    public static function rewardTransactionResponse(): void {}

    /**
     * Ride Response Schema
     */
    #[OA\Schema(
        schema: 'RideResponse',
        description: 'Thông tin chi tiết chuyến xe',
        required: ['id', 'status', 'vehicle_type', 'pickup_address', 'destination_address'],
        properties: [
            new OA\Property(property: 'id', type: 'string', example: '1'),
            new OA\Property(
                property: 'status', 
                description: 'Trạng thái chuyến xe. 1: Nháp, 2: Đang tìm tài xế, 3: Tài xế đã nhận, 4: Đang di chuyển, 5: Hoàn thành, 6: Đã hủy', 
                type: 'integer', 
                example: 1
            ),
            new OA\Property(property: 'status_label', type: 'string', example: 'Đang tìm tài xế'),
            new OA\Property(
                property: 'vehicle_type', 
                description: 'Loại xe. 1: Xe Máy, 2: Ô Tô 4 Chỗ, 3: Ô Tô 7 Chỗ, 4: Ô Tô 9 Chỗ', 
                type: 'integer', 
                example: 1
            ),
            new OA\Property(property: 'pickup_address', type: 'string'),
            new OA\Property(property: 'destination_address', type: 'string'),
            new OA\Property(property: 'distance_km', type: 'number', format: 'float', example: 5.2),
            new OA\Property(property: 'duration_minutes', type: 'integer', example: 15),
            new OA\Property(property: 'total_fare', type: 'number', format: 'float', example: 35000),
            new OA\Property(property: 'discount_amount', type: 'number', format: 'float', example: 5000),
            new OA\Property(property: 'final_fare', type: 'number', format: 'float', example: 30000),
            new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        ]
    )]
    public static function rideResponse(): void {}

    /**
     * Price Estimate Response Schema
     */
    #[OA\Schema(
        schema: 'PriceEstimateResponse',
        description: 'Thông tin ước tính giá cước',
        properties: [
            new OA\Property(property: 'base_fare', type: 'number', format: 'float', example: 15000),
            new OA\Property(property: 'distance_fare', type: 'number', format: 'float', example: 20000),
            new OA\Property(property: 'duration_fare', type: 'number', format: 'float', example: 0),
            new OA\Property(property: 'surge_multiplier', type: 'number', format: 'float', example: 1.0),
            new OA\Property(property: 'subtotal', type: 'number', format: 'float', example: 35000),
            new OA\Property(property: 'discount_amount', type: 'number', format: 'float', example: 0),
            new OA\Property(property: 'total_fare', type: 'number', format: 'float', example: 35000),
        ]
    )]
    /**
     * Wallet Response Schema
     */
    #[OA\Schema(
        schema: 'WalletResponse',
        description: 'Thông tin ví tiền',
        properties: [
            new OA\Property(property: 'id', type: 'string', example: '1'),
            new OA\Property(property: 'balance', type: 'number', format: 'float', example: 150000),
            new OA\Property(property: 'total_earned', type: 'number', format: 'float', example: 2000000),
            new OA\Property(property: 'total_withdrawn', type: 'number', format: 'float', example: 1000000),
        ]
    )]
    public static function walletResponse(): void {}

    /**
     * Wallet Transaction Response Schema
     */
    #[OA\Schema(
        schema: 'WalletTransactionResponse',
        description: 'Thông tin giao dịch ví tiền',
        properties: [
            new OA\Property(property: 'id', type: 'string', example: '123'),
            new OA\Property(property: 'amount', type: 'number', format: 'float', example: 50000),
            new OA\Property(property: 'type', type: 'string', example: 'top_up', description: 'Loại: top_up, fee, withdrawal, refund'),
            new OA\Property(property: 'type_label', type: 'string', example: 'Nạp tiền'),
            new OA\Property(property: 'symbol', type: 'string', example: '+'),
            new OA\Property(property: 'balance_before', type: 'number', format: 'float', example: 100000),
            new OA\Property(property: 'balance_after', type: 'number', format: 'float', example: 150000),
            new OA\Property(property: 'description', type: 'string', example: 'Nạp tiền qua MoMo'),
            new OA\Property(property: 'reference_type', type: 'string', example: 'TopUp'),
            new OA\Property(property: 'reference_id', type: 'string', example: '456'),
            new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
            new OA\Property(property: 'status', type: 'string', example: 'Thành công'),
        ]
    )]
    public static function walletTransactionResponse(): void {}

    /**
     * Subscription Package Response Schema
     */
    #[OA\Schema(
        schema: 'SubscriptionPackageResponse',
        description: 'Thông tin gói thành viên',
        properties: [
            new OA\Property(property: 'id', type: 'string', example: '1'),
            new OA\Property(property: 'name', type: 'string', example: 'Gói VIP Tháng'),
            new OA\Property(property: 'description', type: 'string', example: 'Giảm 50% phí dịch vụ'),
            new OA\Property(property: 'price', type: 'number', format: 'float', example: 100000),
            new OA\Property(property: 'duration_days', type: 'integer', example: 30),
            new OA\Property(property: 'service_fee_reduction_percent', type: 'number', format: 'float', example: 50.0),
        ]
    )]
    public static function subscriptionPackageResponse(): void {}

    /**
     * Top Up Response Schema
     */
    #[OA\Schema(
        schema: 'TopUpResponse',
        description: 'Thông tin khởi tạo nạp tiền',
        properties: [
            new OA\Property(property: 'top_up_id', type: 'string', example: '789'),
            new OA\Property(property: 'external_id', type: 'string', example: 'TX-123456'),
            new OA\Property(property: 'redirect_url', type: 'string', example: 'https://payment.momo.vn/...'),
        ]
    )]
    public static function topUpResponse(): void {}
}
