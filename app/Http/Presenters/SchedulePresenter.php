<?php

namespace App\Http\Presenters;

use App\Models\Member;
use App\Models\Schedule;
use App\Models\WorshipService;
use App\Services\Contracts\IMemberService;
use App\Services\Contracts\IPositionService;
use App\Services\Contracts\IScheduleService;
use Illuminate\Support\Facades\Gate;

/** Monta os props das telas de escala: o que cada pessoa pode ver depende da permissão. */
class SchedulePresenter
{
    public function __construct(
        private readonly IScheduleService $schedules,
        private readonly IMemberService $members,
        private readonly IPositionService $positions,
    ) {}

    public function summary(Schedule $schedule): array
    {
        return [
            'id' => $schedule->id,
            'label' => $schedule->label(),
            'month' => sprintf('%04d-%02d', $schedule->year, $schedule->month),
            'status' => $schedule->status->value,
            'status_label' => $schedule->status->label(),
            'deadline' => $schedule->availability_deadline?->format('d/m'),
            'deadline_iso' => $schedule->availability_deadline?->toDateString(),
        ];
    }

    public function board(Schedule $schedule, Member $viewer): array
    {
        $schedule = $this->schedules->loadFull($schedule);
        $manager = Gate::allows('manage-schedule');
        $showTeam = $manager || ($schedule->isPublished() && Gate::allows('view-full-schedule'));
        $showOwn = $manager || $schedule->isPublished();
        $warnings = $manager ? $this->schedules->warnings($schedule) : ['general' => [], 'services' => []];
        $members = $this->members->list($schedule->ministry_id)->where('active', true)->values();
        $responded = $schedule->availabilityResponses->pluck('member_id')->all();

        return [
            'schedule' => $this->summary($schedule),
            'services' => $schedule->worshipServices->map(fn (WorshipService $s) => [
                'id' => $s->id,
                'date' => $s->date->format('d/m'),
                'iso' => $s->date->toDateString(),
                'weekday' => $s->ordinalLabel(),
                'notes' => $s->notes,
                'assignments' => $s->assignments
                    ->filter(fn ($a) => $showTeam || ($showOwn && $a->member_id === $viewer->id))
                    ->map->only('member_id', 'position_id')->values(),
                'unavailable' => $manager ? $s->unavailabilities->pluck('member_id') : [],
                'setlist_count' => $s->setlistItems->count(),
                'warnings' => $warnings['services'][$s->id] ?? [],
            ]),
            'positions' => $this->positions->list($schedule->ministry_id)
                ->map->only('id', 'name', 'emoji', 'min_per_service', 'max_per_service', 'uses_microphone', 'is_vocal'),
            'members' => $members->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'positions' => $m->positions->modelKeys(),
                'monthly_limit' => $m->monthly_limit,
                'joined' => $m->hasJoined(),
                'responded' => in_array($m->id, $responded, true),
            ]),
            'generalWarnings' => $warnings['general'],
            'myUnavailable' => $this->schedules->unavailableServiceIds($viewer, $schedule),
            'myResponded' => in_array($viewer->id, $responded, true),
            'teamVisible' => $showTeam,
        ];
    }
}
