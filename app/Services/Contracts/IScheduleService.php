<?php

namespace App\Services\Contracts;

use App\Models\Member;
use App\Models\Schedule;
use App\Models\WorshipService;
use App\Services\Scheduling\GenerationResult;
use Illuminate\Database\Eloquent\Collection;

interface IScheduleService
{
    public function list(int $ministryId): Collection;

    public function find(int $ministryId, int $scheduleId): ?Schedule;

    public function findService(int $ministryId, int $serviceId): ?WorshipService;

    /** Abre o mês criando um culto por domingo. */
    public function open(int $ministryId, int $year, int $month, ?string $availabilityDeadline = null): Schedule;

    public function delete(Schedule $schedule): void;

    public function loadFull(Schedule $schedule): Schedule;

    /** @return int[] serviceIds em que o membro marcou que não pode */
    public function unavailableServiceIds(Member $member, Schedule $schedule): array;

    public function hasResponded(Member $member, Schedule $schedule): bool;

    /** @param int[] $serviceIds */
    public function saveAvailability(Member $member, Schedule $schedule, array $serviceIds): void;

    /** Gera e grava a escala do mês inteiro, substituindo o que existia. */
    public function generate(Schedule $schedule): GenerationResult;

    /** @param array<int, array{member_id: int, position_id: int}> $rows */
    public function saveAssignments(WorshipService $service, array $rows): void;

    public function updateService(WorshipService $service, array $data): WorshipService;

    public function publish(Schedule $schedule): Schedule;

    public function unpublish(Schedule $schedule): Schedule;

    /**
     * Problemas da escala atual (depois de ajustes manuais).
     *
     * @return array{general: string[], services: array<int, string[]>}
     */
    public function warnings(Schedule $schedule): array;

    public function upcomingFor(Member $member): Collection;
}
