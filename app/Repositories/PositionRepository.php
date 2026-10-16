<?php

namespace App\Repositories;

use App\Models\Position;
use App\Repositories\Contracts\IPositionRepository;
use Illuminate\Database\Eloquent\Collection;

class PositionRepository implements IPositionRepository
{
    public function __construct(private readonly Position $model) {}

    public function findByMinistry(int $ministryId): Collection
    {
        return $this->model->where('ministry_id', $ministryId)->withCount('members')->orderBy('sort_order')->get();
    }

    public function findInMinistry(int $ministryId, int $positionId): ?Position
    {
        return $this->model->where('ministry_id', $ministryId)->find($positionId);
    }

    public function create(array $data): Position
    {
        return $this->model->create($data);
    }

    public function update(Position $position, array $data): Position
    {
        $position->update($data);

        return $position;
    }

    public function delete(Position $position): bool
    {
        return (bool) $position->delete();
    }
}
