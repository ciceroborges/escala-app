<?php

namespace App\Services;

use App\Models\SetlistItem;
use App\Models\WorshipService;
use App\Repositories\Contracts\ISetlistRepository;
use App\Repositories\Contracts\ISongRepository;
use App\Services\Contracts\ISetlistService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SetlistService implements ISetlistService
{
    public function __construct(
        private readonly ISetlistRepository $setlist,
        private readonly ISongRepository $songs,
    ) {}

    public function list(WorshipService $service): Collection
    {
        return $this->setlist->findByService($service);
    }

    public function find(WorshipService $service, int $itemId): ?SetlistItem
    {
        return $this->setlist->findItem($service, $itemId);
    }

    public function add(WorshipService $service, array $data): SetlistItem
    {
        $song = $this->songs->findInMinistry($service->schedule->ministry_id, (int) $data['song_id'])
            ?? throw ValidationException::withMessages(['song_id' => 'Música não encontrada.']);

        return DB::transaction(function () use ($service, $song, $data) {
            $item = $this->setlist->create([
                'worship_service_id' => $service->id,
                'song_id' => $song->id,
                'sort_order' => $this->setlist->nextSortOrder($service),
                'notes' => $data['notes'] ?? null,
            ]);

            $keys = $song->keys->keyBy('member_id');
            $singers = array_map(fn ($memberId) => [
                'member_id' => (int) $memberId,
                'musical_key' => $keys[$memberId]->musical_key ?? null,
                'starting_key' => $keys[$memberId]->starting_key ?? null,
            ], $data['singers'] ?? []);
            $this->setlist->replaceSingers($item, $singers);

            return $item;
        });
    }

    public function update(SetlistItem $item, array $data): SetlistItem
    {
        return DB::transaction(function () use ($item, $data) {
            $this->setlist->update($item, ['notes' => $data['notes'] ?? null]);
            $this->setlist->replaceSingers($item, collect($data['singers'] ?? [])
                ->unique('member_id')
                ->map(fn ($s) => [
                    'member_id' => (int) $s['member_id'],
                    'musical_key' => $s['musical_key'] ?? null ?: null,
                    'starting_key' => $s['starting_key'] ?? null ?: null,
                ])->values()->all());

            return $item;
        });
    }

    public function remove(SetlistItem $item): void
    {
        $this->setlist->delete($item);
    }

    public function move(WorshipService $service, SetlistItem $item, string $direction): void
    {
        $ids = $this->setlist->findByService($service)->modelKeys();
        $index = array_search($item->id, $ids, true);
        $target = $direction === 'up' ? $index - 1 : $index + 1;

        if ($index === false || ! isset($ids[$target])) {
            return;
        }

        [$ids[$index], $ids[$target]] = [$ids[$target], $ids[$index]];
        $this->setlist->reorder($service, $ids);
    }
}
