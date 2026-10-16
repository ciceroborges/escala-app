<?php

namespace App\Repositories\Contracts;

use App\Models\User;

interface IUserRepository
{
    public function findByGoogleId(string $googleId): ?User;

    public function findByEmail(string $email): ?User;

    public function create(array $data): User;

    public function update(User $user, array $data): User;
}
