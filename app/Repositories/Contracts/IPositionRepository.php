<?php

namespace App\Repositories\Contracts;

use App\Models\Position;
use Illuminate\Database\Eloquent\Collection;

interface IPositionRepository
{
    public function findByMinistry(int $ministryId): Collection;

    public function findInMinistry(int $ministryId, int $positionId): ?Position;

    public function create(array $data): Position;

    public function update(Position $position, array $data): Position;

    public function delete(Position $position): bool;
}
