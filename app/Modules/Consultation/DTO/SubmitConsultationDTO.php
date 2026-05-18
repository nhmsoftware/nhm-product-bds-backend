<?php

namespace App\Modules\Consultation\DTO;

use Illuminate\Http\Request;

final class SubmitConsultationDTO
{
    /**
     * SubmitConsultationDTO constructor.
     *
     * @param string $fullName
     * @param string $phone
     * @param string|null $email
     * @param string|null $projectId
     * @param string|null $projectName
     * @param string|null $content
     */
    public function __construct(
        public readonly string $fullName,
        public readonly string $phone,
        public readonly ?string $email = null,
        public readonly ?string $projectId = null,
        public readonly ?string $projectName = null,
        public readonly ?string $content = null,
    ) {
    }

    /**
     * Khởi tạo DTO từ đối tượng Request.
     *
     * @param Request $request
     * @return self
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            fullName: $request->input('full_name'),
            phone: $request->input('phone'),
            email: $request->input('email'),
            projectId: $request->input('project_id'),
            projectName: $request->input('project_name'),
            content: $request->input('content'),
        );
    }

    /**
     * Chuyển đổi DTO thành mảng.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'full_name' => $this->fullName,
            'phone' => $this->phone,
            'email' => $this->email,
            'project_id' => $this->projectId,
            'project_name' => $this->projectName,
            'content' => $this->content,
        ];
    }
}
