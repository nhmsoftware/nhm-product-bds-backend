<?php

namespace App\Modules\Auth\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use App\Modules\Auth\Models\User;

final class AuthRepository extends BaseRepository implements AuthRepositoryInterface
{
    public function getModel(): string
    {
        return User::class;
    }

    public function findByEmail(string $email)
    {
        return $this->model->where('email', $email)->first();
    }

    public function findByPhone(string $phone)
    {
        return $this->model->where('phone', $phone)->first();
    }

    public function findByStaffCode(string $staffCode)
    {
        return $this->model->where('staff_code', $staffCode)->first();
    }
}
