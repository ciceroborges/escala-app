<?php

namespace App\Services\Scheduling;

final readonly class GenerationResult
{
    /**
     * @param  array<int, array<int, int[]>>  $assignments  serviceId => positionId => memberIds
     * @param  array<int, array<int, string>>  $gaps  serviceId => positionId => motivo da vaga não preenchida
     * @param  array<int, int>  $counts  memberId => quantos cultos no mês
     */
    public function __construct(
        public array $assignments,
        public array $gaps,
        public array $counts,
        public float $score,
    ) {}
}
