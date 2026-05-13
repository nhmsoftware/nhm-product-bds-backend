<?php

namespace App\Modules\Auth\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;

interface AuthRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Tìm người dùng theo email
     */
    public function findByEmail(string $email);

    /**
     * Tìm người dùng theo số điện thoại
     */
    public function findByPhone(string $phone);

    /**
     * Tìm người dùng theo mã nhân viên (dùng làm mã giới thiệu)
     */
    public function findByStaffCode(string $staffCode);
}
