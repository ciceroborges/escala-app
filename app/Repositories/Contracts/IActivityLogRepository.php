<?php

namespace App\Repositories\Contracts;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Collection;

interface IActivityLogRepository
{
    public function create(array $data): ActivityLog;

    public function latestForMinistry(int $ministryId, int $limit = 50): Collection;
}
