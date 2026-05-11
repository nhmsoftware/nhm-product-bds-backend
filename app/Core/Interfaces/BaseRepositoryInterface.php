<?php

namespace App\Core\Interfaces;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface BaseRepositoryInterface
{
    /**
     * Lấy tất cả bản ghi
     */
    public function getAll(array $columns = ['*'], array $relations = []): Collection;

    /**
     * Tìm theo ID (Cơ bản)
     */
    public function find(int|string $id): ?Model;

    /**
     * Tìm theo ID với đầy đủ columns và relations
     */
    public function findById(string|int $id, array $columns = ['*'], array $relations = []): ?Model;

    /**
     * Tìm một bản ghi theo điều kiện array
     */
    public function findByCondition(array $conditions, array $relations = []): ?Model;

    /**
     * Tạo mới bản ghi
     */
    public function create(array $attributes);

    /**
     * Cập nhật theo ID
     */
    public function updateById($id, array $attributes);

    /**
     * Xóa theo ID
     */
    public function deleteById($id);

    /**
     * Trả về instance của model để start query
     */
    public function getModelInstance(): Model;
}
