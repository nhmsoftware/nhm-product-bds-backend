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

    /**
     * Tìm người dùng theo email.
     * 
     * @param string $email
     * @return \App\Modules\Auth\Models\User|null
     */
    public function findByEmail(string $email)
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Tìm người dùng theo số điện thoại.
     * 
     * @param string $phone
     * @return \App\Modules\Auth\Models\User|null
     */
    public function findByPhone(string $phone)
    {
        return $this->model->where('phone', $phone)->first();
    }

    /**
     * Tìm người dùng theo mã nhân viên.
     * 
     * @param string $staffCode
     * @return \App\Modules\Auth\Models\User|null
     */
    public function findByStaffCode(string $staffCode)
    {
        return $this->model->where('staff_code', $staffCode)->first();
    }
}
