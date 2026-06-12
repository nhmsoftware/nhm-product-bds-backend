<?php

declare(strict_types=1);

namespace App\Modules\Consultation\DTO;

use Illuminate\Http\Request;

final class RequestCallbackDTO
{
    public function __construct(
        public readonly string $fullName,
        public readonly string $phone,
        public readonly string $preferredCallbackTime,
        public readonly ?string $email = null,
        public readonly ?string $projectId = null,
        public readonly ?string $projectName = null,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            fullName: $request->input('full_name'),
            phone: $request->input('phone'),
            preferredCallbackTime: $request->input('preferred_callback_time'),
            email: $request->input('email'),
            projectId: $request->input('project_id'),
            projectName: $request->input('project_name'),
        );
    }

    public function toArray(): array
    {
        return [
            'full_name' => $this->fullName,
            'phone' => $this->phone,
            'email' => $this->email,
            'project_id' => $this->projectId,
            'project_name' => $this->projectName,
            'content' => 'Yêu cầu gọi lại',
            'request_type' => 'callback',
            'preferred_callback_time' => $this->preferredCallbackTime,
        ];
    }
}
