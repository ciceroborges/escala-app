<?php

namespace App\Services;

use App\Models\Position;
use App\Repositories\Contracts\IPositionRepository;
use App\Services\Contracts\IPositionService;
use Illuminate\Database\Eloquent\Collection;

class PositionService implements IPositionService
{
    public function __construct(private readonly IPositionRepository $positions) {}

    public function list(int $ministryId): Collection
    {
        return $this->positions->findByMinistry($ministryId);
    }

    public function find(int $ministryId, int $positionId): ?Position
    {
        return $this->positions->findInMinistry($ministryId, $positionId);
    }

    public function create(int $ministryId, array $data): Position
    {
        $order = $this->positions->findByMinistry($ministryId)->max('sort_order') + 1;

        return $this->positions->create($this->attributes($data) + ['ministry_id' => $ministryId, 'sort_order' => $order]);
    }

    public function update(Position $position, array $data): Position
    {
        return $this->positions->update($position, $this->attributes($data));
    }

    public function delete(Position $position): void
    {
        $this->positions->delete($position);
    }

    private function attributes(array $data): array
    {
        $min = (int) $data['min_per_service'];

        return [
            'name' => $data['name'],
            'emoji' => $data['emoji'] ?? null,
            'uses_microphone' => (bool) ($data['uses_microphone'] ?? false),
            'is_vocal' => (bool) ($data['is_vocal'] ?? false),
            'min_per_service' => $min,
            'max_per_service' => max($min, (int) $data['max_per_service']),
        ];
    }
}
