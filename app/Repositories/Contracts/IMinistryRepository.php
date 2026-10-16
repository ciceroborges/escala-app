<?php

namespace App\Repositories\Contracts;

use App\Models\Ministry;
use Illuminate\Database\Eloquent\Collection;

interface IMinistryRepository
{
    public function create(array $data): Ministry;

    public function update(Ministry $ministry, array $data): Ministry;

    /** Ministérios em que o usuário é membro ativo. */
    public function findForUser(int $userId): Collection;
}
