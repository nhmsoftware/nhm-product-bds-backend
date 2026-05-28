<?php

namespace App\Modules\Learning\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;

interface QuizAttemptRepositoryInterface extends BaseRepositoryInterface
{
    public function getDraftsByUserAndQuizIds(string $userId, array $quizIds): \Illuminate\Database\Eloquent\Collection;

    public function deleteByUserAndQuizIds(string $userId, array $quizIds): int;

    public function countCorrectByUserAndQuizIds(string $userId, array $quizIds): int;

    public function countByQuizIds(array $quizIds): int;

    public function findByUserAndQuiz(string $userId, string $quizId);
}
