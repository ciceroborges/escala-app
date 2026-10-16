<?php

namespace App\Repositories\Contracts;

use App\Models\Schedule;
use App\Models\WorshipService;
use Illuminate\Database\Eloquent\Collection;

interface IScheduleRepository
{
    public function findByMinistry(int $ministryId): Collection;

    public function findInMinistry(int $ministryId, int $scheduleId): ?Schedule;

    public function findByMonth(int $ministryId, int $year, int $month): ?Schedule;

    /** Escala com cultos, escalações, indisponibilidades e setlists carregados. */
    public function loadFull(Schedule $schedule): Schedule;

    public function create(array $data): Schedule;

    public function update(Schedule $schedule, array $data): Schedule;

    public function delete(Schedule $schedule): bool;

    public function createService(Schedule $schedule, string $date): WorshipService;

    public function findService(int $ministryId, int $serviceId): ?WorshipService;

    public function updateService(WorshipService $service, array $data): WorshipService;

    /** @param array<int, array{member_id: int, position_id: int}> $rows */
    public function replaceAssignments(WorshipService $service, array $rows): void;

    /** @param int[] $serviceIds */
    public function replaceUnavailability(int $memberId, Schedule $schedule, array $serviceIds): void;

    public function markResponded(int $memberId, Schedule $schedule): void;

    /** Próximos cultos (a partir de hoje) em que o membro está escalado. */
    public function upcomingForMember(int $memberId, int $limit = 6): Collection;
}
