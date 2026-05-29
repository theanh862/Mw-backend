<?php

namespace App\Repositories;

interface UserRepositoryInterface
{
    public function getAll();
    public function findById(int $id);
    public function findByEmail(string $email);
    public function findByGoogleId(string $googleId);
    public function createOrUpdateFromGoogle(array $data);
    public function count();
}
