<?php

namespace App\Services;

use App\Enums\ScheduleStatus;
use App\Models\Member;
use App\Models\Schedule;
use App\Models\WorshipService;
use App\Repositories\Contracts\IMemberRepository;
use App\Repositories\Contracts\IPositionRepository;
use App\Repositories\Contracts\IScheduleRepository;
use App\Services\Contracts\IActivityLogService;
use App\Services\Contracts\IScheduleGenerator;
use App\Services\Contracts\IScheduleService;
use App\Services\Scheduling\GenerationInput;
use App\Services\Scheduling\GenerationResult;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScheduleService implements IScheduleService
{
    public function __construct(
        private readonly IScheduleRepository $schedules,
        private readonly IMemberRepository $members,
        private readonly IPositionRepository $positions,
        private readonly IScheduleGenerator $generator,
        private readonly IActivityLogService $activity,
    ) {}

    public function list(int $ministryId): Collection
    {
        return $this->schedules->findByMinistry($ministryId);
    }

    public function find(int $ministryId, int $scheduleId): ?Schedule
    {
        return $this->schedules->findInMinistry($ministryId, $scheduleId);
    }

    public function findService(int $ministryId, int $serviceId): ?WorshipService
    {
        return $this->schedules->findService($ministryId, $serviceId);
    }

    public function open(int $ministryId, int $year, int $month, ?string $availabilityDeadline = null): Schedule
    {
        if ($this->schedules->findByMonth($ministryId, $year, $month)) {
            throw ValidationException::withMessages(['month' => 'Esse mês já foi aberto.']);
        }

        return DB::transaction(function () use ($ministryId, $year, $month, $availabilityDeadline) {
            $schedule = $this->schedules->create([
                'ministry_id' => $ministryId,
                'year' => $year,
                'month' => $month,
                'status' => ScheduleStatus::Draft,
                'availability_deadline' => $availabilityDeadline,
            ]);

            $day = CarbonImmutable::create($year, $month, 1);
            $day = $day->isSunday() ? $day : $day->next(CarbonImmutable::SUNDAY);
            while ($day->month === $month) {
                $this->schedules->createService($schedule, $day->toDateString());
                $day = $day->addWeek();
            }

            return $schedule;
        });
    }

    public function delete(Schedule $schedule): void
    {
        $this->schedules->delete($schedule);
    }

    public function loadFull(Schedule $schedule): Schedule
    {
        return $this->schedules->loadFull($schedule);
    }

    public function unavailableServiceIds(Member $member, Schedule $schedule): array
    {
        return $member->unavailabilities()
            ->whereIn('worship_service_id', $schedule->worshipServices()->pluck('id'))
            ->pluck('worship_service_id')
            ->all();
    }

    public function hasResponded(Member $member, Schedule $schedule): bool
    {
        return $schedule->availabilityResponses()->where('member_id', $member->id)->exists();
    }

    public function saveAvailability(Member $member, Schedule $schedule, array $serviceIds): void
    {
        $valid = $schedule->worshipServices()->pluck('id')->all();

        DB::transaction(function () use ($member, $schedule, $serviceIds, $valid) {
            $unavailable = array_values(array_intersect($serviceIds, $valid));
            $this->schedules->replaceUnavailability($member->id, $schedule, $unavailable);
            $this->schedules->markResponded($member->id, $schedule);
            $this->activity->log('schedules.availability', "marcou a disponibilidade de {$member->name} em {$schedule->label()}",
                $schedule, ['member_id' => $member->id, 'unavailable' => $unavailable]);
        });
    }

    public function generate(Schedule $schedule): GenerationResult
    {
        $schedule = $this->loadFull($schedule);
        $result = $this->generator->generate($this->generationInput($schedule));

        DB::transaction(function () use ($schedule, $result) {
            foreach ($schedule->worshipServices as $service) {
                $rows = [];
                foreach ($result->assignments[$service->id] ?? [] as $positionId => $memberIds) {
                    foreach ($memberIds as $memberId) {
                        $rows[] = ['member_id' => $memberId, 'position_id' => $positionId];
                    }
                }
                $this->schedules->replaceAssignments($service, $rows);
            }
            $this->activity->log('schedules.generated', "gerou a escala de {$schedule->label()}", $schedule,
                ['gaps' => $result->gaps, 'counts' => $result->counts]);
        });

        return $result;
    }

    public function saveAssignments(WorshipService $service, array $rows): void
    {
        $ministryId = $service->schedule->ministry_id;
        $memberIds = $this->members->findByMinistry($ministryId)->modelKeys();
        $positionIds = $this->positions->findByMinistry($ministryId)->modelKeys();

        $rows = collect($rows)
            ->filter(fn ($r) => in_array((int) $r['member_id'], $memberIds, true) && in_array((int) $r['position_id'], $positionIds, true))
            ->map(fn ($r) => ['member_id' => (int) $r['member_id'], 'position_id' => (int) $r['position_id']])
            ->unique(fn ($r) => $r['member_id'].'-'.$r['position_id'])
            ->values()
            ->all();

        $before = $service->assignments()->get(['member_id', 'position_id'])->toArray();
        DB::transaction(function () use ($service, $rows, $before) {
            $this->schedules->replaceAssignments($service, $rows);
            $this->activity->log('worship_services.team_updated', "alterou a equipe do culto de {$service->date->format('d/m/Y')}",
                $service, ['old' => $before, 'new' => $rows]);
        });
    }

    public function updateService(WorshipService $service, array $data): WorshipService
    {
        return $this->schedules->updateService($service, ['notes' => $data['notes'] ?? null]);
    }

    public function publish(Schedule $schedule): Schedule
    {
        return $this->schedules->update($schedule, ['status' => ScheduleStatus::Published]);
    }

    public function unpublish(Schedule $schedule): Schedule
    {
        return $this->schedules->update($schedule, ['status' => ScheduleStatus::Draft]);
    }

    public function warnings(Schedule $schedule): array
    {
        $schedule = $this->loadFull($schedule);
        $members = $this->members->findByMinistry($schedule->ministry_id)->keyBy('id');
        $positions = $this->positions->findByMinistry($schedule->ministry_id)->keyBy('id');

        $general = [];
        $byService = [];
        $counts = [];

        foreach ($schedule->worshipServices as $service) {
            $messages = [];
            $assignments = $service->assignments;
            $unavailable = $service->unavailabilities->pluck('member_id')->all();

            foreach ($positions as $position) {
                $count = $assignments->where('position_id', $position->id)->count();
                if ($count < $position->min_per_service) {
                    $messages[] = 'Falta'.($position->min_per_service - $count > 1 ? 'm ' : ' ')
                        .($position->min_per_service - $count)." em {$position->name}.";
                } elseif ($count > $position->max_per_service) {
                    $messages[] = "{$position->name} acima do máximo ({$count} de {$position->max_per_service}).";
                }
            }

            foreach ($assignments->groupBy('member_id') as $memberId => $held) {
                $member = $members[$memberId] ?? null;
                if (! $member) {
                    continue;
                }
                $counts[$memberId] = ($counts[$memberId] ?? 0) + 1;

                if (in_array($memberId, $unavailable, true)) {
                    $messages[] = "{$member->name} marcou que não pode neste dia.";
                }
                foreach ($held as $assignment) {
                    if (! $member->positions->contains('id', $assignment->position_id)) {
                        $messages[] = "{$member->name} não tem a função {$positions[$assignment->position_id]?->name}.";
                    }
                }
                $instruments = $held->filter(fn ($a) => ! ($positions[$a->position_id]?->uses_microphone ?? true));
                if ($instruments->count() > 1) {
                    $messages[] = "{$member->name} está em mais de um instrumento.";
                }
            }

            $byService[$service->id] = $messages;
        }

        foreach ($counts as $memberId => $count) {
            $member = $members[$memberId];
            if ($member->monthly_limit !== null && $count > $member->monthly_limit) {
                $general[] = "{$member->name} está em {$count} cultos (limite: {$member->monthly_limit}).";
            }
        }

        return ['general' => $general, 'services' => $byService];
    }

    public function upcomingFor(Member $member): Collection
    {
        return $this->schedules->upcomingForMember($member->id);
    }

    private function generationInput(Schedule $schedule): GenerationInput
    {
        $services = $schedule->worshipServices->mapWithKeys(fn ($s) => [$s->id => $s->date->toDateString()])->all();

        $positions = $this->positions->findByMinistry($schedule->ministry_id)->mapWithKeys(fn ($p) => [$p->id => [
            'name' => $p->name,
            'min' => $p->min_per_service,
            'max' => $p->max_per_service,
            'microphone' => $p->uses_microphone,
        ]])->all();

        $members = $this->members->findByMinistry($schedule->ministry_id, onlyActive: true)
            ->filter(fn ($m) => $m->positions->isNotEmpty())
            ->mapWithKeys(fn ($m) => [$m->id => [
                'name' => $m->name,
                'positions' => $m->positions->modelKeys(),
                'limit' => $m->monthly_limit,
                'fixed' => $m->fixed_position_id,
            ]])->all();

        $unavailable = [];
        foreach ($schedule->worshipServices as $service) {
            foreach ($service->unavailabilities as $u) {
                $unavailable[$u->member_id][] = $service->id;
            }
        }

        return new GenerationInput($services, $positions, $members, $unavailable);
    }
}
