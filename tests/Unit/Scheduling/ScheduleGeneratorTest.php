<?php

namespace Tests\Unit\Scheduling;

use App\Services\Scheduling\GenerationInput;
use App\Services\Scheduling\GenerationResult;
use App\Services\Scheduling\ScheduleGenerator;
use PHPUnit\Framework\TestCase;

/** Ministério real (outubro/2026) como base: 4 domingos, um único tecladista, líder fixa. */
class ScheduleGeneratorTest extends TestCase
{
    private const VOCAL = 1, DM = 2, KEYS = 3, ACOUSTIC = 4, GUITAR = 5, BASS = 6, DRUMS = 7, DESK = 8;

    private const SERVICES = [101 => '2026-10-04', 102 => '2026-10-11', 103 => '2026-10-18', 104 => '2026-10-25'];

    private function positions(): array
    {
        return [
            self::VOCAL => ['name' => 'Vocal', 'min' => 3, 'max' => 4, 'microphone' => true],
            self::DM => ['name' => 'DM', 'min' => 0, 'max' => 1, 'microphone' => true],
            self::KEYS => ['name' => 'Teclado', 'min' => 1, 'max' => 1, 'microphone' => false],
            self::ACOUSTIC => ['name' => 'Violão', 'min' => 0, 'max' => 1, 'microphone' => false],
            self::GUITAR => ['name' => 'Guitarra', 'min' => 0, 'max' => 1, 'microphone' => false],
            self::BASS => ['name' => 'Baixo', 'min' => 0, 'max' => 1, 'microphone' => false],
            self::DRUMS => ['name' => 'Bateria', 'min' => 1, 'max' => 1, 'microphone' => false],
            self::DESK => ['name' => 'Mesa', 'min' => 1, 'max' => 1, 'microphone' => false],
        ];
    }

    private function members(): array
    {
        $m = fn (string $name, array $positions, ?int $limit = 2, ?int $fixed = null) => compact('name', 'positions', 'limit', 'fixed');

        return [
            1 => $m('Lucimar', [self::VOCAL], null, self::VOCAL),
            2 => $m('João Lucas', [self::KEYS, self::VOCAL], null),
            3 => $m('Dani', [self::VOCAL]),
            4 => $m('Cami', [self::VOCAL]),
            5 => $m('Léo', [self::VOCAL, self::GUITAR]),
            6 => $m('Patrick', [self::VOCAL, self::ACOUSTIC]),
            7 => $m('Sara', [self::VOCAL]),
            8 => $m('Cícero', [self::VOCAL, self::BASS]),
            9 => $m('Géssica', [self::VOCAL]),
            10 => $m('Ricardo', [self::VOCAL]),
            11 => $m('Giandro', [self::DM, self::BASS]),
            12 => $m('Assis', [self::DRUMS]),
            13 => $m('Eliézer', [self::DRUMS]),
            14 => $m('Rômulo', [self::DESK, self::DRUMS]),
            15 => $m('Iury', [self::ACOUSTIC]),
            16 => $m('João Robert', [self::DESK]),
            17 => $m('Jack', [self::DESK]),
        ];
    }

    private function generate(array $unavailable = [], ?array $members = null): GenerationResult
    {
        return (new ScheduleGenerator)->generate(new GenerationInput(
            self::SERVICES, $this->positions(), $members ?? $this->members(), $unavailable, attempts: 200, seed: 42,
        ));
    }

    public function test_fills_every_minimum_slot_in_a_normal_month(): void
    {
        $result = $this->generate();

        $this->assertSame([], $result->gaps);
        foreach ($result->assignments as $slots) {
            foreach ($this->positions() as $positionId => $position) {
                $this->assertGreaterThanOrEqual($position['min'], count($slots[$positionId]));
                $this->assertLessThanOrEqual($position['max'], count($slots[$positionId]));
            }
        }
    }

    public function test_respects_monthly_limit_counting_services_not_positions(): void
    {
        $result = $this->generate();

        foreach ($this->members() as $memberId => $member) {
            if ($member['limit'] !== null) {
                $this->assertLessThanOrEqual($member['limit'], $result->counts[$memberId], $member['name']);
            }
        }
    }

    public function test_fixed_member_is_in_every_service(): void
    {
        $result = $this->generate();

        foreach ($result->assignments as $slots) {
            $this->assertContains(1, $slots[self::VOCAL]);
        }
    }

    public function test_only_keyboard_player_covers_every_service(): void
    {
        $result = $this->generate();

        foreach ($result->assignments as $slots) {
            $this->assertSame([2], $slots[self::KEYS]);
        }
    }

    public function test_nobody_plays_two_instruments_in_the_same_service(): void
    {
        $result = $this->generate();
        $positions = $this->positions();

        foreach ($result->assignments as $slots) {
            $instruments = [];
            foreach ($slots as $positionId => $memberIds) {
                if (! $positions[$positionId]['microphone']) {
                    array_push($instruments, ...$memberIds);
                }
            }
            $this->assertSame(array_unique($instruments), $instruments);
        }
    }

    public function test_never_schedules_unavailable_member(): void
    {
        $result = $this->generate([12 => [101, 102], 3 => [101]]);

        foreach ([101, 102] as $serviceId) {
            $this->assertNotContains(12, array_merge(...array_values($result->assignments[$serviceId])));
        }
        $this->assertNotContains(3, array_merge(...array_values($result->assignments[101])));
    }

    public function test_spreads_limited_members_across_non_consecutive_weeks(): void
    {
        $result = $this->generate();
        $services = array_keys(self::SERVICES);
        $backToBack = [];

        foreach ([3, 4, 7, 9, 10] as $memberId) {
            $weeks = [];
            foreach ($services as $index => $serviceId) {
                if (in_array($memberId, array_merge(...array_values($result->assignments[$serviceId])), true)) {
                    $weeks[] = $index;
                }
            }
            for ($i = 1; $i < count($weeks); $i++) {
                if ($weeks[$i] - $weeks[$i - 1] === 1) {
                    $backToBack[] = $memberId;
                }
            }
        }

        $this->assertSame([], $backToBack, 'membros em semanas seguidas');
    }

    public function test_explains_why_a_slot_could_not_be_filled(): void
    {
        $result = $this->generate([12 => [103], 13 => [103], 14 => [103]]);

        $this->assertArrayHasKey(self::DRUMS, $result->gaps[103]);
        $this->assertStringContainsString('3 indisponível', $result->gaps[103][self::DRUMS]);
    }

    public function test_same_seed_gives_same_schedule(): void
    {
        $this->assertEquals($this->generate()->assignments, $this->generate()->assignments);
    }
}
