<?php

namespace App\Services\Scheduling;

/**
 * Dados puros para gerar uma escala — sem Eloquent, para o algoritmo ser testável isolado.
 */
final readonly class GenerationInput
{
    /**
     * @param  array<int, string>  $services  serviceId => data (Y-m-d), em ordem cronológica
     * @param  array<int, array{name: string, min: int, max: int, microphone: bool}>  $positions
     * @param  array<int, array{name: string, positions: int[], limit: ?int, fixed: ?int}>  $members
     * @param  array<int, int[]>  $unavailable  memberId => serviceIds
     */
    public function __construct(
        public array $services,
        public array $positions,
        public array $members,
        public array $unavailable = [],
        public int $attempts = 300,
        public ?int $seed = null,
    ) {}

    public function isAvailable(int $memberId, int $serviceId): bool
    {
        return ! in_array($serviceId, $this->unavailable[$memberId] ?? [], true);
    }
}
