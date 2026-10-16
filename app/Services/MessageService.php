<?php

namespace App\Services;

use App\Models\Schedule;
use App\Repositories\Contracts\IMemberRepository;
use App\Repositories\Contracts\IPositionRepository;
use App\Repositories\Contracts\IScheduleRepository;
use App\Services\Contracts\IMessageService;
use Illuminate\Support\Collection;

class MessageService implements IMessageService
{
    public function __construct(
        private readonly IScheduleRepository $schedules,
        private readonly IMemberRepository $members,
        private readonly IPositionRepository $positions,
    ) {}

    public function scheduleMessage(Schedule $schedule): string
    {
        $schedule = $this->schedules->loadFull($schedule);
        $names = $this->members->findByMinistry($schedule->ministry_id)->pluck('name', 'id');
        $positions = $this->positions->findByMinistry($schedule->ministry_id);

        $blocks = [];
        foreach ($schedule->worshipServices as $service) {
            $lines = [$service->date->format('d/m').' – '.$service->ordinalLabel()];
            $microphones = 0;

            foreach ($positions as $position) {
                $people = $service->assignments->where('position_id', $position->id)
                    ->map(fn ($a) => $names[$a->member_id] ?? null)->filter()->values();
                if ($people->isEmpty()) {
                    continue;
                }
                $lines[] = trim(($position->emoji ?? '').' '.$position->name).': '.$people->implode(', ');
                if ($position->uses_microphone) {
                    $microphones += $people->count();
                }
            }

            if ($microphones) {
                $lines[] = "⚠️ Total de microfones: {$microphones}";
            }
            if ($service->notes) {
                $lines[] = '📝 '.$service->notes;
            }
            $blocks[] = implode("\n", $lines);
        }

        return '⏺️ Escala '.$schedule->label()."\n\n"
            .implode("\n\n", $blocks)
            ."\n\n---\n\n💡 Confiram seus dias com atenção! Qualquer imprevisto, avisem com antecedência para organizarmos a substituição.";
    }

    public function repertoireMessage(Schedule $schedule): string
    {
        $schedule = $this->schedules->loadFull($schedule);
        $ministryMembers = $this->members->findByMinistry($schedule->ministry_id);
        $names = $ministryMembers->pluck('name', 'id');
        $vocalPositionIds = $this->positions->findByMinistry($schedule->ministry_id)->where('is_vocal', true)->modelKeys();

        $blocks = [];
        foreach ($schedule->worshipServices as $service) {
            $vocals = $service->assignments->whereIn('position_id', $vocalPositionIds)
                ->map(fn ($a) => $names[$a->member_id] ?? null)->filter()->unique()->values();

            $lines = [$service->date->format('d/m').($vocals->isNotEmpty() ? ' – '.$this->joinNames($vocals) : '')];
            $lines[] = '';

            foreach ($service->setlistItems as $item) {
                $lines[] = $this->songLine($item, $names);
            }
            if ($service->setlistItems->isEmpty()) {
                $lines[] = '(repertório a definir)';
            }
            if ($service->notes) {
                $lines[] = '📝 '.$service->notes;
            }

            $blocks[] = implode("\n", $lines);
        }

        return '🎶 REPERTÓRIO — '.mb_strtoupper($schedule->label())."\n\n".implode("\n\n", $blocks);
    }

    private function songLine($item, Collection $names): string
    {
        $line = trim(($item->song->primaryOption?->emoji ?? '').' '.$item->song->title);

        $voices = $item->singers->map(function ($singer) use ($names) {
            $name = $names[$singer->member_id] ?? '?';

            return $name.': '.($singer->keyLabel() ?? '[tom?]');
        });

        if ($voices->isNotEmpty()) {
            $line .= ' — '.$voices->implode(' / ');
        }
        if ($item->notes) {
            $line .= " ({$item->notes})";
        }

        return $line;
    }

    private function joinNames(Collection $names): string
    {
        return $names->count() > 1
            ? $names->slice(0, -1)->implode(', ').' e '.$names->last()
            : $names->first();
    }
}
