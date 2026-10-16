<?php

namespace App\Repositories;

use App\Models\Assignment;
use App\Models\AvailabilityResponse;
use App\Models\Schedule;
use App\Models\Unavailability;
use App\Models\WorshipService;
use App\Repositories\Contracts\IScheduleRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ScheduleRepository implements IScheduleRepository
{
    public function __construct(
        private readonly Schedule $model,
        private readonly WorshipService $services,
    ) {}

    public function findByMinistry(int $ministryId): Collection
    {
        return $this->model->where('ministry_id', $ministryId)
            ->withCount('worshipServices')
            ->orderByDesc('year')->orderByDesc('month')
            ->get();
    }

    public function findInMinistry(int $ministryId, int $scheduleId): ?Schedule
    {
        return $this->model->where('ministry_id', $ministryId)->find($scheduleId);
    }

    public function findByMonth(int $ministryId, int $year, int $month): ?Schedule
    {
        return $this->model->where(compact('year', 'month'))->where('ministry_id', $ministryId)->first();
    }

    public function loadFull(Schedule $schedule): Schedule
    {
        return $schedule->load([
            'worshipServices.assignments',
            'worshipServices.unavailabilities',
            'worshipServices.setlistItems.song.primaryOption',
            'worshipServices.setlistItems.singers',
            'availabilityResponses',
        ]);
    }

    public function create(array $data): Schedule
    {
        return $this->model->create($data);
    }

    public function update(Schedule $schedule, array $data): Schedule
    {
        $schedule->update($data);

        return $schedule;
    }

    public function delete(Schedule $schedule): bool
    {
        return (bool) $schedule->delete();
    }

    public function createService(Schedule $schedule, string $date): WorshipService
    {
        return $schedule->worshipServices()->create(['date' => $date]);
    }

    public function findService(int $ministryId, int $serviceId): ?WorshipService
    {
        return $this->services->whereHas('schedule', fn ($q) => $q->where('ministry_id', $ministryId))
            ->with('schedule')
            ->find($serviceId);
    }

    public function updateService(WorshipService $service, array $data): WorshipService
    {
        $service->update($data);

        return $service;
    }

    public function replaceAssignments(WorshipService $service, array $rows): void
    {
        DB::transaction(function () use ($service, $rows) {
            Assignment::where('worship_service_id', $service->id)->delete();
            foreach ($rows as $row) {
                Assignment::create($row + ['worship_service_id' => $service->id]);
            }
        });
    }

    public function replaceUnavailability(int $memberId, Schedule $schedule, array $serviceIds): void
    {
        DB::transaction(function () use ($memberId, $schedule, $serviceIds) {
            Unavailability::where('member_id', $memberId)
                ->whereIn('worship_service_id', $schedule->worshipServices()->pluck('id'))
                ->delete();
            foreach ($serviceIds as $serviceId) {
                Unavailability::create(['member_id' => $memberId, 'worship_service_id' => $serviceId]);
            }
        });
    }

    public function markResponded(int $memberId, Schedule $schedule): void
    {
        AvailabilityResponse::firstOrCreate(['member_id' => $memberId, 'schedule_id' => $schedule->id]);
    }

    public function upcomingForMember(int $memberId, int $limit = 6): Collection
    {
        return $this->services
            ->whereHas('assignments', fn ($q) => $q->where('member_id', $memberId))
            ->whereHas('schedule', fn ($q) => $q->where('status', 'published'))
            ->whereDate('date', '>=', today())
            ->with(['assignments' => fn ($q) => $q->where('member_id', $memberId)->with('position')])
            ->orderBy('date')
            ->limit($limit)
            ->get();
    }
}
