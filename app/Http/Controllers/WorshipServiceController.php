<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignmentsRequest;
use App\Models\WorshipService;
use App\Repositories\Contracts\IPositionRepository;
use App\Services\Contracts\IMemberService;
use App\Services\Contracts\IScheduleService;
use App\Services\Contracts\ISetlistService;
use App\Services\Contracts\ISongService;
use App\Support\CurrentMinistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class WorshipServiceController extends Controller
{
    public function __construct(
        private readonly IScheduleService $schedules,
        private readonly ISetlistService $setlist,
        private readonly ISongService $songs,
        private readonly IMemberService $members,
        private readonly IPositionRepository $positions,
        private readonly CurrentMinistry $current,
    ) {}

    public function show(int $service): Response
    {
        $model = $this->find($service);
        $manager = Gate::allows('manage-schedule') || Gate::allows('manage-repertoire');
        abort_unless($manager || $model->schedule->isPublished(), 403);

        $positions = $this->positions->findByMinistry($this->current->id());
        $vocalIds = $positions->where('is_vocal', true)->modelKeys();
        $names = $this->members->list($this->current->id())->pluck('name', 'id');
        $assignments = $model->assignments()->get();

        return Inertia::render('Services/Show', [
            'service' => [
                'id' => $model->id,
                'date' => $model->date->format('d/m'),
                'weekday' => $model->ordinalLabel(),
                'notes' => $model->notes,
                'schedule_id' => $model->schedule_id,
                'schedule_label' => $model->schedule->label(),
                'published' => $model->schedule->isPublished(),
            ],
            'team' => $positions->map(fn ($p) => [
                'position' => trim(($p->emoji ?? '').' '.$p->name),
                'people' => $assignments->where('position_id', $p->id)->map(fn ($a) => $names[$a->member_id] ?? '?')->values(),
            ])->filter(fn ($row) => $row['people']->isNotEmpty())->values(),
            // voz principal sai de quem está no vocal deste culto
            'vocalists' => $assignments->whereIn('position_id', $vocalIds)->pluck('member_id')->unique()
                ->map(fn ($id) => ['id' => $id, 'name' => $names[$id] ?? '?'])->values(),
            'setlist' => $this->setlist->list($model)->map(fn ($item) => [
                'id' => $item->id,
                'notes' => $item->notes,
                'song' => [
                    ...$item->song->only('id', 'title', 'artist', 'youtube_url', 'chords_url'),
                    'tag' => $item->song->primaryOption?->label(),
                    'tag_emoji' => $item->song->primaryOption?->emoji,
                ],
                'singers' => $item->singers->map(fn ($s) => [
                    'member_id' => $s->member_id,
                    'name' => $s->member?->name,
                    'musical_key' => $s->musical_key,
                    'starting_key' => $s->starting_key,
                ]),
            ]),
            'songs' => Gate::allows('manage-repertoire')
                ? $this->songs->list($this->current->id())->map(fn ($s) => [
                    'id' => $s->id,
                    'title' => $s->title,
                    'artist' => $s->artist,
                    'tag_emoji' => $s->primaryOption?->emoji,
                    'singer_ids' => $s->keys->pluck('member_id'),
                ])
                : [],
        ]);
    }

    public function updateTeam(AssignmentsRequest $request, int $service): RedirectResponse
    {
        $model = $this->find($service);
        $this->schedules->saveAssignments($model, $request->validated('assignments', []));
        $this->schedules->updateService($model, ['notes' => $request->validated('notes')]);

        return back()->with('success', 'Domingo '.$model->date->format('d/m').' salvo.');
    }

    private function find(int $id): WorshipService
    {
        return $this->schedules->findService($this->current->id(), $id) ?? abort(404);
    }
}
