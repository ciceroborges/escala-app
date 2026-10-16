<?php

namespace App\Http\Controllers;

use App\Http\Presenters\SchedulePresenter;
use App\Http\Requests\AvailabilityRequest;
use App\Http\Requests\OpenScheduleRequest;
use App\Models\Schedule;
use App\Services\Contracts\IMemberService;
use App\Services\Contracts\IMessageService;
use App\Services\Contracts\IScheduleService;
use App\Support\CurrentMinistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function __construct(
        private readonly IScheduleService $schedules,
        private readonly IMemberService $members,
        private readonly IMessageService $messages,
        private readonly SchedulePresenter $presenter,
        private readonly CurrentMinistry $current,
    ) {}

    public function index(): Response
    {
        $schedules = $this->schedules->list($this->current->id());
        $next = now()->startOfMonth()->addMonth();
        $taken = $schedules->map(fn ($s) => sprintf('%04d-%02d', $s->year, $s->month))->all();
        while (in_array($next->format('Y-m'), $taken, true)) {
            $next->addMonth();
        }

        return Inertia::render('Schedules/Index', [
            'schedules' => $schedules
                ->filter(fn ($s) => Gate::allows('manage-schedule') || $s->isPublished() || $s->firstDay()->endOfMonth()->isFuture())
                ->map(fn ($s) => $this->presenter->summary($s) + ['services_count' => $s->worship_services_count])
                ->values(),
            'suggestedMonth' => $next->format('Y-m'),
        ]);
    }

    public function store(OpenScheduleRequest $request): RedirectResponse
    {
        [$year, $month] = array_map('intval', explode('-', $request->validated('month')));
        $schedule = $this->schedules->open($this->current->id(), $year, $month, $request->validated('availability_deadline'));

        return redirect()->route('schedules.show', $schedule->id)
            ->with('success', 'Mês aberto! Avise a equipe para marcar quando não pode.');
    }

    public function show(int $schedule): Response
    {
        return Inertia::render('Schedules/Show', $this->presenter->board($this->find($schedule), $this->current->member()));
    }

    public function destroy(int $schedule): RedirectResponse
    {
        $this->schedules->delete($this->find($schedule));

        return redirect()->route('schedules.index')->with('success', 'Mês excluído.');
    }

    public function availability(AvailabilityRequest $request, int $schedule): RedirectResponse
    {
        $model = $this->find($schedule);
        $member = $this->current->member();

        // líder pode marcar por quem ainda não entrou no app
        if ($request->filled('member_id') && (int) $request->member_id !== $member->id) {
            abort_unless(Gate::allows('manage-schedule'), 403);
            $member = $this->members->find($this->current->id(), (int) $request->member_id) ?? abort(404);
        }

        $this->schedules->saveAvailability($member, $model, $request->validated('unavailable', []));

        return back()->with('success', 'Disponibilidade salva. Obrigado!');
    }

    public function generate(int $schedule): RedirectResponse
    {
        $result = $this->schedules->generate($this->find($schedule));
        $gaps = collect($result->gaps)->flatten()->count();

        return back()->with('success', $gaps
            ? "Escala gerada, mas {$gaps} vaga(s) ficaram sem ninguém. Veja os avisos em cada domingo."
            : 'Escala gerada! Confira e ajuste o que quiser.');
    }

    public function publish(int $schedule): RedirectResponse
    {
        $this->schedules->publish($this->find($schedule));

        return redirect()->route('schedules.messages', $schedule)->with('success', 'Escala publicada! Agora é só mandar no grupo.');
    }

    public function unpublish(int $schedule): RedirectResponse
    {
        $this->schedules->unpublish($this->find($schedule));

        return back()->with('success', 'A escala voltou para rascunho.');
    }

    public function messages(int $schedule): Response
    {
        $model = $this->find($schedule);

        return Inertia::render('Schedules/Messages', [
            'schedule' => $this->presenter->summary($model),
            'scheduleText' => $this->messages->scheduleMessage($model),
            'repertoireText' => $this->messages->repertoireMessage($model),
        ]);
    }

    private function find(int $id): Schedule
    {
        return $this->schedules->find($this->current->id(), $id) ?? abort(404);
    }
}
