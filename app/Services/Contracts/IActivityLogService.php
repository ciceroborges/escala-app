<?php

namespace App\Services\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface IActivityLogService
{
    /** Registra a ação; sem usuário logado, o autor é o webmaster. */
    public function log(string $action, string $description, ?Model $subject = null, array $properties = [], ?int $ministryId = null): void;

    public function latest(int $ministryId, int $limit = 50): Collection;
}
