<?php

namespace App\Modules\Auth\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;

interface AuthRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Tìm người dùng theo email.
     * 
     * @param string $email
     * @return \App\Modules\Auth\Models\User|null
     */
    public function findByEmail(string $email);

    /**
     * Tìm người dùng theo số điện thoại.
     * 
     * @param string $phone
     * @return \App\Modules\Auth\Models\User|null
     */
    public function findByPhone(string $phone);

    /**
     * Tìm người dùng theo mã nhân viên (dùng làm mã giới thiệu).
     * 
     * @param string $staffCode
     * @return \App\Modules\Auth\Models\User|null
     */
    public function findByStaffCode(string $staffCode);
}
