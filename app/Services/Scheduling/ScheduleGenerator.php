<?php

namespace App\Services\Scheduling;

use App\Services\Contracts\IScheduleGenerator;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Monta a escala do mês por tentativas: cada tentativa é gulosa (culto a culto, função mais
 * escassa primeiro) com desempate aleatório; fica a de menor custo.
 *
 * Duras: disponibilidade, função, limite mensal (conta cultos, não funções) e no máximo um
 * instrumento por pessoa por culto — funções com microfone (vocal, DM) podem somar.
 * Preferências: rodízio justo, evitar cultos seguidos, completar vocal com quem já está no culto.
 */
class ScheduleGenerator implements IScheduleGenerator
{
    private const GAP_COST = 1000;

    private const BACK_TO_BACK_COST = 10;

    private GenerationInput $input;

    private Randomizer $random;

    public function generate(GenerationInput $input): GenerationResult
    {
        $this->input = $input;
        $this->random = new Randomizer(new Mt19937($input->seed));

        $best = null;
        for ($i = 0; $i < max(1, $input->attempts); $i++) {
            $attempt = $this->attempt();
            if ($best === null || $attempt->score < $best->score) {
                $best = $attempt;
            }
        }

        return $best;
    }

    private function attempt(): GenerationResult
    {
        $assignments = [];
        $counts = array_fill_keys(array_keys($this->input->members), 0);
        $previous = [];

        foreach (array_keys($this->input->services) as $serviceId) {
            $slots = array_fill_keys(array_keys($this->input->positions), []);
            $inService = [];

            $this->placeFixedMembers($serviceId, $slots, $inService);

            foreach ($this->positionsByScarcity($serviceId) as $positionId) {
                $min = $this->input->positions[$positionId]['min'];
                $this->fill($serviceId, $positionId, $min, $slots, $inService, $counts, $previous, false);
            }

            // vaga opcional (até o máximo) só com quem já está no culto: não gasta cota do mês
            foreach (array_keys($this->input->positions) as $positionId) {
                $max = $this->input->positions[$positionId]['max'];
                $this->fill($serviceId, $positionId, $max, $slots, $inService, $counts, $previous, true);
            }

            foreach (array_keys($inService) as $memberId) {
                $counts[$memberId]++;
            }

            $assignments[$serviceId] = $slots;
            $previous = $inService;
        }

        $gaps = $this->gaps($assignments, $counts);

        return new GenerationResult($assignments, $gaps, $counts, $this->cost($assignments, $counts));
    }

    private function placeFixedMembers(int $serviceId, array &$slots, array &$inService): void
    {
        foreach ($this->input->members as $memberId => $member) {
            $positionId = $member['fixed'];
            if ($positionId === null || ! isset($slots[$positionId]) || ! $this->input->isAvailable($memberId, $serviceId)) {
                continue;
            }
            $slots[$positionId][] = $memberId;
            $inService[$memberId][] = $positionId;
        }
    }

    /** Funções com menos candidatos disponíveis primeiro, para não "gastar" quem só ela tem. */
    private function positionsByScarcity(int $serviceId): array
    {
        $supply = [];
        foreach (array_keys($this->input->positions) as $positionId) {
            $supply[$positionId] = count(array_filter(
                $this->input->members,
                fn ($m, $id) => in_array($positionId, $m['positions'], true) && $this->input->isAvailable($id, $serviceId),
                ARRAY_FILTER_USE_BOTH,
            ));
        }
        asort($supply);

        return array_keys($supply);
    }

    private function fill(int $serviceId, int $positionId, int $target, array &$slots, array &$inService,
        array $counts, array $previous, bool $onlyAlreadyInService): void
    {
        while (count($slots[$positionId]) < $target) {
            $best = null;
            $bestScore = INF;

            foreach ($this->input->members as $memberId => $member) {
                if ($onlyAlreadyInService && ! isset($inService[$memberId])) {
                    continue;
                }
                if (! $this->canTake($memberId, $serviceId, $positionId, $slots, $inService, $counts)) {
                    continue;
                }

                $score = $counts[$memberId] * 10
                    + (isset($previous[$memberId]) ? 5 : 0)
                    - (isset($inService[$memberId]) ? 3 : 0)
                    + $this->random->getFloat(0, 1);

                if ($score < $bestScore) {
                    $bestScore = $score;
                    $best = $memberId;
                }
            }

            if ($best === null) {
                return;
            }

            $slots[$positionId][] = $best;
            $inService[$best][] = $positionId;
        }
    }

    private function canTake(int $memberId, int $serviceId, int $positionId, array $slots, array $inService, array $counts): bool
    {
        $member = $this->input->members[$memberId];

        if (! in_array($positionId, $member['positions'], true)
            || in_array($memberId, $slots[$positionId], true)
            || ! $this->input->isAvailable($memberId, $serviceId)) {
            return false;
        }

        if (! isset($inService[$memberId]) && $member['limit'] !== null && $counts[$memberId] >= $member['limit']) {
            return false;
        }

        if (! $this->input->positions[$positionId]['microphone']) {
            foreach ($inService[$memberId] ?? [] as $heldId) {
                if (! $this->input->positions[$heldId]['microphone']) {
                    return false;
                }
            }
        }

        return true;
    }

    private function gaps(array $assignments, array $counts): array
    {
        $gaps = [];
        foreach ($assignments as $serviceId => $slots) {
            foreach ($slots as $positionId => $memberIds) {
                $missing = $this->input->positions[$positionId]['min'] - count($memberIds);
                if ($missing > 0) {
                    $gaps[$serviceId][$positionId] = $this->explainGap($serviceId, $positionId, $missing, $counts);
                }
            }
        }

        return $gaps;
    }

    private function explainGap(int $serviceId, int $positionId, int $missing, array $counts): string
    {
        $holders = array_filter($this->input->members, fn ($m) => in_array($positionId, $m['positions'], true));
        $name = $this->input->positions[$positionId]['name'];

        if (! $holders) {
            return "Faltou {$missing} em {$name}: ninguém do ministério tem essa função.";
        }

        $unavailable = $atLimit = 0;
        foreach (array_keys($holders) as $memberId) {
            if (! $this->input->isAvailable($memberId, $serviceId)) {
                $unavailable++;
            } elseif ($holders[$memberId]['limit'] !== null && $counts[$memberId] >= $holders[$memberId]['limit']) {
                $atLimit++;
            }
        }

        $reasons = array_filter([
            $unavailable ? "{$unavailable} indisponível(is)" : null,
            $atLimit ? "{$atLimit} no limite do mês" : null,
        ]);

        return "Faltou {$missing} em {$name}: ".count($holders).' na função'
            .($reasons ? ', '.implode(', ', $reasons) : ', já ocupados em outra função').'.';
    }

    private function cost(array $assignments, array $counts): float
    {
        $cost = 0.0;
        $previous = [];

        foreach ($assignments as $slots) {
            foreach ($slots as $positionId => $memberIds) {
                $cost += max(0, $this->input->positions[$positionId]['min'] - count($memberIds)) * self::GAP_COST;
            }
            $current = array_unique(array_merge(...array_values($slots) ?: [[]]));
            $cost += count(array_intersect($current, $previous)) * self::BACK_TO_BACK_COST;
            $previous = $current;
        }

        // rodízio: só quem tem limite entra na conta; fixos e "sem limite" vão quantas vezes precisar
        $rotating = array_filter($counts, fn ($id) => $this->input->members[$id]['limit'] !== null
            && $this->input->members[$id]['fixed'] === null, ARRAY_FILTER_USE_KEY);
        if ($rotating) {
            $mean = array_sum($rotating) / count($rotating);
            foreach ($rotating as $count) {
                $cost += ($count - $mean) ** 2;
            }
        }

        return $cost;
    }
}
