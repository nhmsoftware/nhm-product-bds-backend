<?php

namespace App\Modules\Auth\DTO;

use Illuminate\Http\Request;

final class RegisterDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $phone,
        public readonly string $password,
        public readonly string $account_type,
        public readonly ?string $referral_code = null,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->validated('name'),
            email: $request->validated('email'),
            phone: $request->validated('phone'),
            password: $request->validated('password'),
            account_type: $request->validated('account_type'),
            referral_code: $request->validated('referral_code'),
        );
    }

    public function toArray(): array
    {
        return [
            'name'          => $this->name,
            'email'         => $this->email,
            'phone'         => $this->phone,
            'password'      => $this->password,
            'account_type'  => $this->account_type,
            'referral_code' => $this->referral_code,
        ];
    }
}
