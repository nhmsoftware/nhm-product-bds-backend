<?php

namespace App\Modules\Dashboard\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Dashboard\DTO\GetCommentsDTO;

interface AdminCommentServiceInterface
{
    /**
     * @param string $userId
     * @param GetCommentsDTO $dto
     * @return ServiceReturn
     */
    public function getList(string $userId, GetCommentsDTO $dto): ServiceReturn;

    /**
     * @param string $userId
     * @param string $commentId
     * @param string $sourceType
     * @return ServiceReturn
     */
    public function deleteComment(string $userId, string $commentId, string $sourceType): ServiceReturn;
}
