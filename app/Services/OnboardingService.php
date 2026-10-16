<?php

namespace App\Services;

use App\Models\Assignment;
use App\Repositories\Contracts\IMemberRepository;
use App\Repositories\Contracts\IScheduleRepository;
use App\Repositories\Contracts\ISongRepository;
use App\Services\Contracts\IOnboardingService;

class OnboardingService implements IOnboardingService
{
    public function __construct(
        private readonly IMemberRepository $members,
        private readonly ISongRepository $songs,
        private readonly IScheduleRepository $schedules,
    ) {}

    public function checklist(int $ministryId): array
    {
        $members = $this->members->findByMinistry($ministryId, onlyActive: true);
        $schedules = $this->schedules->findByMinistry($ministryId);
        $hasAssignments = Assignment::whereHas('worshipService.schedule', fn ($q) => $q->where('ministry_id', $ministryId))->exists();

        return [
            [
                'key' => 'members',
                'title' => 'Cadastre a equipe',
                'description' => 'Nome, e-mail e o que cada um faz (canta, toca, mesa…). Quem entrar com esse e-mail já cai no ministério.',
                'done' => $members->count() > 1 && $members->every(fn ($m) => $m->positions->isNotEmpty()),
                'route' => 'members.index',
            ],
            [
                'key' => 'songs',
                'title' => 'Cadastre as músicas',
                'description' => 'O repertório do ministério, com o tom de cada cantor.',
                'done' => $this->songs->findByMinistry($ministryId)->isNotEmpty(),
                'route' => 'songs.index',
            ],
            [
                'key' => 'schedule',
                'title' => 'Abra o primeiro mês',
                'description' => 'Os domingos são criados sozinhos e a equipe já pode marcar quando não pode.',
                'done' => $schedules->isNotEmpty(),
                'route' => 'schedules.index',
            ],
            [
                'key' => 'generate',
                'title' => 'Gere a escala',
                'description' => 'Um toque e o app monta a escala respeitando funções, limites e disponibilidade.',
                'done' => $hasAssignments,
                'route' => 'schedules.index',
            ],
            [
                'key' => 'publish',
                'title' => 'Publique e mande no WhatsApp',
                'description' => 'A mensagem sai pronta, no formato do grupo.',
                'done' => $schedules->contains(fn ($s) => $s->isPublished()),
                'route' => 'schedules.index',
            ],
        ];
    }
}
