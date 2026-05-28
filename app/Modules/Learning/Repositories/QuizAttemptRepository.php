<?php

namespace App\Modules\Learning\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Learning\Interfaces\QuizAttemptRepositoryInterface;
use App\Modules\Learning\Models\QuizAttempt;

final class QuizAttemptRepository extends BaseRepository implements QuizAttemptRepositoryInterface
{
    public function getModel(): string
    {
        return QuizAttempt::class;
    }

    public function getDraftsByUserAndQuizIds(string $userId, array $quizIds): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->where('user_id', $userId)
            ->whereIn('quiz_id', $quizIds)
            ->where('is_draft', true)
            ->get();
    }

    public function deleteByUserAndQuizIds(string $userId, array $quizIds): int
    {
        return $this->model->where('user_id', $userId)
            ->whereIn('quiz_id', $quizIds)
            ->delete();
    }

    public function countCorrectByUserAndQuizIds(string $userId, array $quizIds): int
    {
        return $this->model->where('user_id', $userId)
            ->whereIn('quiz_id', $quizIds)
            ->where('is_correct', true)
            ->count();
    }

    public function countByQuizIds(array $quizIds): int
    {
        return $this->model->whereIn('quiz_id', $quizIds)->count();
    }

    public function findByUserAndQuiz(string $userId, string $quizId)
    {
        return $this->model->where('user_id', $userId)
            ->where('quiz_id', $quizId)
            ->first();
    }
}
