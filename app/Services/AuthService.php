<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\IMemberRepository;
use App\Repositories\Contracts\IUserRepository;
use App\Services\Contracts\IAuthService;
use Illuminate\Support\Str;

class AuthService implements IAuthService
{
    public function __construct(
        private readonly IUserRepository $users,
        private readonly IMemberRepository $members,
    ) {}

    public function loginWithGoogle(string $googleId, string $email, string $name, ?string $avatarUrl): User
    {
        $email = Str::lower($email);
        $data = ['google_id' => $googleId, 'email' => $email, 'name' => $name, 'avatar_url' => $avatarUrl];

        $user = $this->users->findByGoogleId($googleId) ?? $this->users->findByEmail($email);
        $user = $user ? $this->users->update($user, $data) : $this->users->create($data);

        $this->members->linkPendingByEmail($email, $user->id);

        return $user;
    }

    public function loginForDevelopment(string $email, ?string $name = null): User
    {
        $email = Str::lower($email);
        $user = $this->users->findByEmail($email)
            ?? $this->users->create(['email' => $email, 'name' => $name ?: Str::before($email, '@')]);

        $this->members->linkPendingByEmail($email, $user->id);

        return $user;
    }
}
