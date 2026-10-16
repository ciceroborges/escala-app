<?php

namespace App\Services;

use App\Models\Ministry;
use App\Models\User;
use App\Repositories\Contracts\IActivityLogRepository;
use App\Services\Contracts\IActivityLogService;
use App\Support\CurrentMinistry;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ActivityLogService implements IActivityLogService
{
    public function __construct(
        private readonly IActivityLogRepository $logs,
        private readonly CurrentMinistry $current,
    ) {}

    public function log(string $action, string $description, ?Model $subject = null, array $properties = [], ?int $ministryId = null): void
    {
        $this->logs->create([
            'user_id' => auth()->id() ?? User::WEBMASTER_ID,
            'ministry_id' => $ministryId ?? $this->ministryOf($subject),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'description' => mb_substr($description, 0, 255),
            'properties' => $properties ?: null,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
        ]);
    }

    public function latest(int $ministryId, int $limit = 50): Collection
    {
        return $this->logs->latestForMinistry($ministryId, $limit);
    }

    private function ministryOf(?Model $subject): ?int
    {
        if ($subject instanceof Ministry) {
            return $subject->getKey();
        }

        return $subject?->getAttribute('ministry_id')
            ?? $subject?->schedule?->ministry_id
            ?? $subject?->worshipService?->schedule?->ministry_id
            ?? $this->current->member()?->ministry_id;
    }
}
