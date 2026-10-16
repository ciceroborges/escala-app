<?php

namespace App\Repositories;

use App\Models\Ministry;
use App\Repositories\Contracts\IMinistryRepository;
use Illuminate\Database\Eloquent\Collection;

class MinistryRepository implements IMinistryRepository
{
    public function __construct(private readonly Ministry $model) {}

    public function create(array $data): Ministry
    {
        return $this->model->create($data);
    }

    public function update(Ministry $ministry, array $data): Ministry
    {
        $ministry->update($data);

        return $ministry;
    }

    public function findForUser(int $userId): Collection
    {
        return $this->model
            ->whereHas('members', fn ($q) => $q->where('user_id', $userId)->where('active', true))
            ->orderBy('name')
            ->get();
    }
}
