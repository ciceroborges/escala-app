<?php

namespace App\Http\Controllers;

use App\Enums\MemberRole;
use App\Enums\Permission;
use App\Http\Requests\MemberRequest;
use App\Models\Member;
use App\Services\Contracts\IMemberService;
use App\Services\Contracts\IPositionService;
use App\Support\CurrentMinistry;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    public function __construct(
        private readonly IMemberService $members,
        private readonly IPositionService $positions,
        private readonly CurrentMinistry $current,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Members/Index', [
            'members' => $this->members->list($this->current->id())->map(fn (Member $m) => [
                'id' => $m->id,
                'name' => $m->name,
                'email' => $m->email,
                'phone' => $m->phone,
                'role' => $m->role->value,
                'permissions' => $m->permissions ?? [],
                'monthly_limit' => $m->monthly_limit,
                'fixed_position_id' => $m->fixed_position_id,
                'active' => $m->active,
                'joined' => $m->hasJoined(),
                'positions' => $m->positions->modelKeys(),
                'is_me' => $m->id === $this->current->member()->id,
            ]),
            'positions' => $this->positions->list($this->current->id())->map->only('id', 'name', 'emoji'),
            'roles' => collect(MemberRole::cases())->map(fn ($r) => ['value' => $r->value, 'label' => $r->label()]),
            'permissions' => collect(Permission::cases())->map(fn ($p) => ['value' => $p->value, 'label' => $p->label()]),
        ]);
    }

    public function store(MemberRequest $request): RedirectResponse
    {
        $member = $this->members->create($this->current->id(), $request->validated());

        return back()->with('success', "{$member->name} foi adicionado(a).");
    }

    public function update(MemberRequest $request, int $member): RedirectResponse
    {
        $model = $this->members->find($this->current->id(), $member) ?? abort(404);
        $this->members->update($model, $request->validated(), $this->current->member());

        return back()->with('success', 'Alterações salvas.');
    }

    public function destroy(int $member): RedirectResponse
    {
        $model = $this->members->find($this->current->id(), $member) ?? abort(404);
        $this->members->delete($model, $this->current->member());

        return back()->with('success', "{$model->name} foi removido(a).");
    }
}
