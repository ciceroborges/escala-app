<?php

namespace App\Repositories\Contracts;

use App\Models\SetlistItem;
use App\Models\WorshipService;
use Illuminate\Database\Eloquent\Collection;

interface ISetlistRepository
{
    public function findByService(WorshipService $service): Collection;

    public function findItem(WorshipService $service, int $itemId): ?SetlistItem;

    public function nextSortOrder(WorshipService $service): int;

    public function create(array $data): SetlistItem;

    public function update(SetlistItem $item, array $data): SetlistItem;

    public function delete(SetlistItem $item): bool;

    /** @param array<int, array{member_id: int, musical_key: ?string, starting_key: ?string}> $singers */
    public function replaceSingers(SetlistItem $item, array $singers): void;

    /** @param int[] $orderedIds */
    public function reorder(WorshipService $service, array $orderedIds): void;
}
