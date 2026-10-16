<?php

namespace App\Http\Controllers;

use App\Http\Requests\MinistrySettingsRequest;
use App\Http\Requests\PositionRequest;
use App\Http\Requests\SongCategoriesRequest;
use App\Services\Contracts\IActivityLogService;
use App\Services\Contracts\IMinistryService;
use App\Services\Contracts\IPositionService;
use App\Services\Contracts\ISongService;
use App\Support\CurrentMinistry;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(
        private readonly IMinistryService $ministries,
        private readonly IPositionService $positions,
        private readonly ISongService $songs,
        private readonly IActivityLogService $activity,
        private readonly CurrentMinistry $current,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Settings/Index', [
            'settings' => $this->current->ministry()->only('name', 'church_name'),
            'positions' => $this->positions->list($this->current->id())->map(fn ($p) => [
                ...$p->only('id', 'name', 'emoji', 'uses_microphone', 'is_vocal', 'min_per_service', 'max_per_service'),
                'members_count' => $p->members_count,
            ]),
            'categories' => $this->songs->categories($this->current->id())->mapWithKeys(fn ($c) => [$c->level->value => [
                'name' => $c->name,
                'active' => $c->active,
                'options' => $c->options->map->only('id', 'name', 'emoji'),
            ]]),
            'activity' => $this->activity->latest($this->current->id(), 40)->map(fn ($log) => [
                'id' => $log->id,
                'who' => $log->user?->name ?? '—',
                'what' => $log->description,
                'when' => $log->created_at->locale('pt_BR')->diffForHumans(),
                'at' => $log->created_at->format('d/m/Y H:i'),
            ]),
        ]);
    }

    public function updateMinistry(MinistrySettingsRequest $request): RedirectResponse
    {
        $this->ministries->update($this->current->ministry(), $request->validated());

        return back()->with('success', 'Ministério atualizado.');
    }

    public function storePosition(PositionRequest $request): RedirectResponse
    {
        $this->positions->create($this->current->id(), $request->validated());

        return back()->with('success', 'Função criada.');
    }

    public function updatePosition(PositionRequest $request, int $position): RedirectResponse
    {
        $model = $this->positions->find($this->current->id(), $position) ?? abort(404);
        $this->positions->update($model, $request->validated());

        return back()->with('success', 'Função atualizada.');
    }

    public function destroyPosition(int $position): RedirectResponse
    {
        $model = $this->positions->find($this->current->id(), $position) ?? abort(404);
        $this->positions->delete($model);

        return back()->with('success', 'Função removida.');
    }

    public function updateCategories(SongCategoriesRequest $request): RedirectResponse
    {
        $this->songs->saveCategories($this->current->id(), $request->validated());

        return back()->with('success', 'Classificações salvas.');
    }
}
