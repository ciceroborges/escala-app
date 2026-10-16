<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMinistryRequest;
use App\Repositories\Contracts\IMemberRepository;
use App\Services\Contracts\IMinistryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MinistryController extends Controller
{
    public function __construct(
        private readonly IMinistryService $ministries,
        private readonly IMemberRepository $members,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Ministries/Index', [
            'ministries' => $this->ministries->listForUser($user)->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'church_name' => $m->church_name,
                'current' => $request->session()->get('ministry_id') === $m->id,
            ]),
        ]);
    }

    public function store(StoreMinistryRequest $request): RedirectResponse
    {
        $ministry = $this->ministries->create($request->user(), $request->validated());
        $request->session()->put('ministry_id', $ministry->id);

        return redirect()->route('dashboard')->with('success', 'Ministério criado! Agora siga os primeiros passos.');
    }

    public function switch(Request $request, int $ministry): RedirectResponse
    {
        abort_unless($this->members->findForUser($ministry, $request->user()->id), 403);
        $request->session()->put('ministry_id', $ministry);

        return redirect()->route('dashboard');
    }
}
