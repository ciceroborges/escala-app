<?php

namespace App\Repositories;

use App\Models\ActivityLog;
use App\Repositories\Contracts\IActivityLogRepository;
use Illuminate\Database\Eloquent\Collection;

class ActivityLogRepository implements IActivityLogRepository
{
    public function __construct(private readonly ActivityLog $model) {}

    public function create(array $data): ActivityLog
    {
        return $this->model->create($data);
    }

    public function latestForMinistry(int $ministryId, int $limit = 50): Collection
    {
        return $this->model->where('ministry_id', $ministryId)->with('user:id,name')->latest('id')->limit($limit)->get();
    }
}
