<?php

namespace App\Http\Controllers;

use App\Http\Requests\SongRequest;
use App\Repositories\Contracts\IPositionRepository;
use App\Services\Contracts\IMemberService;
use App\Services\Contracts\ISongService;
use App\Support\CurrentMinistry;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SongController extends Controller
{
    public function __construct(
        private readonly ISongService $songs,
        private readonly IMemberService $members,
        private readonly IPositionRepository $positions,
        private readonly CurrentMinistry $current,
    ) {}

    public function index(): Response
    {
        $ministryId = $this->current->id();
        $vocalIds = $this->positions->findByMinistry($ministryId)->where('is_vocal', true)->modelKeys();

        return Inertia::render('Songs/Index', [
            'songs' => $this->songs->list($ministryId)->map(fn ($s) => [
                ...$s->only('id', 'title', 'artist', 'youtube_url', 'chords_url', 'primary_option_id', 'secondary_option_id'),
                'keys' => $s->keys->map->only('member_id', 'musical_key', 'starting_key'),
            ]),
            'categories' => $this->songs->categories($ministryId)->filter->active->mapWithKeys(fn ($c) => [$c->level->value => [
                'name' => $c->name,
                'options' => $c->options->map->only('id', 'name', 'emoji'),
            ]]),
            'singers' => $this->members->list($ministryId)
                ->filter(fn ($m) => $m->positions->contains(fn ($p) => in_array($p->id, $vocalIds, true)))
                ->map->only('id', 'name')->values(),
        ]);
    }

    public function store(SongRequest $request): RedirectResponse
    {
        $song = $this->songs->create($this->current->id(), $request->validated());

        return back()->with('success', "\"{$song->title}\" adicionada ao repertório.");
    }

    public function update(SongRequest $request, int $song): RedirectResponse
    {
        $model = $this->songs->find($this->current->id(), $song) ?? abort(404);
        $this->songs->update($model, $request->validated());

        return back()->with('success', 'Música atualizada.');
    }

    public function destroy(int $song): RedirectResponse
    {
        $model = $this->songs->find($this->current->id(), $song) ?? abort(404);
        $this->songs->delete($model);

        return back()->with('success', 'Música removida.');
    }
}
