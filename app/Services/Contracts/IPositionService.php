<?php

namespace App\Services\Contracts;

use App\Models\Position;
use Illuminate\Database\Eloquent\Collection;

interface IPositionService
{
    public function list(int $ministryId): Collection;

    public function find(int $ministryId, int $positionId): ?Position;

    public function create(int $ministryId, array $data): Position;

    public function update(Position $position, array $data): Position;

    public function delete(Position $position): void;
}
