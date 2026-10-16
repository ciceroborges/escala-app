<?php

namespace App\Services\Contracts;

use App\Models\Ministry;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface IMinistryService
{
    public function listForUser(User $user): Collection;

    /** Cria o ministério já pronto para uso: criador como admin, funções e classificação padrão. */
    public function create(User $user, array $data): Ministry;

    public function update(Ministry $ministry, array $data): Ministry;
}
