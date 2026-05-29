<?php

namespace App\Repositories;

interface OrderRepositoryInterface
{
    public function getAll();
    public function getByUserId(int $userId);
    public function findById(string $id);
    public function create(array $data);
    public function update(string $id, array $data);
    public function delete(string $id);
    public function count();
    public function totalRevenue();
}
