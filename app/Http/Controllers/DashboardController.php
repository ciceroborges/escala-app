<?php

namespace App\Http\Controllers;

use App\Enums\ScheduleStatus;
use App\Services\Contracts\IOnboardingService;
use App\Services\Contracts\IScheduleService;
use App\Support\CurrentMinistry;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly IScheduleService $schedules,
        private readonly IOnboardingService $onboarding,
        private readonly CurrentMinistry $current,
    ) {}

    public function __invoke(): Response
    {
        $member = $this->current->member();

        $pendingAvailability = $this->schedules->list($this->current->id())
            ->filter(fn ($s) => $s->status === ScheduleStatus::Draft
                && $s->firstDay()->endOfMonth()->isFuture()
                && ! $this->schedules->hasResponded($member, $s))
            ->map(fn ($s) => [
                'id' => $s->id,
                'label' => $s->label(),
                'deadline' => $s->availability_deadline?->format('d/m'),
            ])->values();

        return Inertia::render('Dashboard', [
            'checklist' => Gate::allows('manage-schedule') ? $this->onboarding->checklist($this->current->id()) : [],
            'upcoming' => $this->schedules->upcomingFor($member)->map(fn ($s) => [
                'id' => $s->id,
                'date' => $s->date->format('d/m'),
                'weekday' => $s->ordinalLabel(),
                'relative' => $s->date->locale('pt_BR')->diffForHumans(['parts' => 1]),
                'positions' => $s->assignments->map(fn ($a) => trim(($a->position->emoji ?? '').' '.$a->position->name))->values(),
            ]),
            'pendingAvailability' => $pendingAvailability,
        ]);
    }
}
