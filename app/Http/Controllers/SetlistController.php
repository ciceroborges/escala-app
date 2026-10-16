<?php

namespace App\Http\Controllers;

use App\Http\Requests\SetlistItemRequest;
use App\Models\SetlistItem;
use App\Models\WorshipService;
use App\Services\Contracts\IScheduleService;
use App\Services\Contracts\ISetlistService;
use App\Support\CurrentMinistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SetlistController extends Controller
{
    public function __construct(
        private readonly ISetlistService $setlist,
        private readonly IScheduleService $schedules,
        private readonly CurrentMinistry $current,
    ) {}

    public function store(SetlistItemRequest $request, int $service): RedirectResponse
    {
        $this->setlist->add($this->service($service), $request->validated());

        return back()->with('success', 'Música adicionada.');
    }

    public function update(SetlistItemRequest $request, int $service, int $item): RedirectResponse
    {
        $model = $this->service($service);
        $this->setlist->update($this->item($model, $item), $request->validated());

        return back()->with('success', 'Música atualizada.');
    }

    public function destroy(int $service, int $item): RedirectResponse
    {
        $model = $this->service($service);
        $this->setlist->remove($this->item($model, $item));

        return back()->with('success', 'Música removida do culto.');
    }

    public function move(Request $request, int $service, int $item): RedirectResponse
    {
        $request->validate(['direction' => ['required', 'in:up,down']]);
        $model = $this->service($service);
        $this->setlist->move($model, $this->item($model, $item), $request->direction);

        return back();
    }

    private function service(int $id): WorshipService
    {
        return $this->schedules->findService($this->current->id(), $id) ?? abort(404);
    }

    private function item(WorshipService $service, int $id): SetlistItem
    {
        return $this->setlist->find($service, $id) ?? abort(404);
    }
}
