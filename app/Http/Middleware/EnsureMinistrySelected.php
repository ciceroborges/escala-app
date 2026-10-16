<?php

namespace App\Http\Middleware;

use App\Repositories\Contracts\IMemberRepository;
use App\Services\Contracts\IMinistryService;
use App\Support\CurrentMinistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Resolve o ministério da sessão; sem um válido, manda escolher/criar. */
class EnsureMinistrySelected
{
    public function __construct(
        private readonly IMemberRepository $members,
        private readonly IMinistryService $ministries,
        private readonly CurrentMinistry $current,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $ministryId = $request->session()->get('ministry_id');
        $member = $ministryId ? $this->members->findForUser((int) $ministryId, $user->id) : null;

        if (! $member) {
            $only = $this->ministries->listForUser($user);
            if ($only->count() !== 1) {
                return redirect()->route('ministries.index');
            }
            $member = $this->members->findForUser($only->first()->id, $user->id);
            $request->session()->put('ministry_id', $member->ministry_id);
        }

        $this->current->set($member);

        return $next($request);
    }
}
