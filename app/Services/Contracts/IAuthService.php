<?php

namespace App\Services\Contracts;

use App\Models\User;

interface IAuthService
{
    /** Cria/atualiza o usuário do Google e vincula os pré-cadastros feitos com o e-mail dele. */
    public function loginWithGoogle(string $googleId, string $email, string $name, ?string $avatarUrl): User;

    /** Só em ambiente local: entra como o e-mail informado, sem Google. */
    public function loginForDevelopment(string $email, ?string $name = null): User;
}
