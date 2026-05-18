<?php

namespace App\Modules\Auth\DTO;

use Illuminate\Http\Request;

final class UpdateProfileDTO
{
    /**
     * UpdateProfileDTO constructor.
     */
    public function __construct(
        public readonly string $userId,
        public readonly string $name,
        public readonly string $email,
        public readonly string $phone,
        public readonly ?string $address = null,
    ) {
    }

    /**
     * Create a DTO from a Request.
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            userId: (string) auth('api')->id(),
            name: $request->validated('name'),
            email: $request->validated('email'),
            phone: $request->validated('phone'),
            address: $request->validated('address'),
        );
    }

    /**
     * Convert the DTO to an array for database update.
     */
    public function toArray(): array
    {
        return [
            'name'    => $this->name,
            'email'   => $this->email,
            'phone'   => $this->phone,
            'address' => $this->address,
        ];
    }
}
