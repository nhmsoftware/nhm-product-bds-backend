<?php

namespace App\Modules\Auth\DTO;

use Illuminate\Http\Request;

final class UpdateEmployeeProfileDTO
{
    /**
     * UpdateEmployeeProfileDTO constructor.
     */
    public function __construct(
        public readonly string $userId,
        public readonly string $name,
        public readonly string $phone,
        public readonly string $email,
        public readonly ?string $avatar,
        public readonly ?string $dob,
        public readonly ?string $address,
        public readonly ?string $bankAccountName,
        public readonly ?string $bankAccountNumber,
        public readonly ?string $bankName,
        public readonly ?string $education,
        public readonly ?string $major,
        public readonly ?string $experience,
        public readonly ?string $employeeTitle,
    ) {
    }

    /**
     * Create DTO from validated request payload.
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            userId: (string) auth('api')->id(),
            name: $request->validated('name'),
            phone: $request->validated('phone'),
            email: $request->validated('email'),
            avatar: $request->validated('avatar'),
            dob: $request->validated('dob'),
            address: $request->validated('address'),
            bankAccountName: $request->validated('bank_account_name'),
            bankAccountNumber: $request->validated('bank_account_number'),
            bankName: $request->validated('bank_name'),
            education: $request->validated('education'),
            major: $request->validated('major'),
            experience: $request->validated('experience'),
            employeeTitle: $request->validated('employee_title'),
        );
    }
}
