<?php

namespace App\Repositories;

use App\Models\SetlistItem;
use App\Models\SetlistItemSinger;
use App\Models\WorshipService;
use App\Repositories\Contracts\ISetlistRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SetlistRepository implements ISetlistRepository
{
    public function __construct(private readonly SetlistItem $model) {}

    public function findByService(WorshipService $service): Collection
    {
        return $this->model->where('worship_service_id', $service->id)
            ->with(['song.primaryOption', 'song.secondaryOption', 'singers.member'])
            ->orderBy('sort_order')
            ->get();
    }

    public function findItem(WorshipService $service, int $itemId): ?SetlistItem
    {
        return $this->model->where('worship_service_id', $service->id)->find($itemId);
    }

    public function nextSortOrder(WorshipService $service): int
    {
        return (int) $this->model->where('worship_service_id', $service->id)->max('sort_order') + 1;
    }

    public function create(array $data): SetlistItem
    {
        return $this->model->create($data);
    }

    public function update(SetlistItem $item, array $data): SetlistItem
    {
        $item->update($data);

        return $item;
    }

    public function delete(SetlistItem $item): bool
    {
        return (bool) $item->delete();
    }

    public function replaceSingers(SetlistItem $item, array $singers): void
    {
        DB::transaction(function () use ($item, $singers) {
            SetlistItemSinger::where('setlist_item_id', $item->id)->delete();
            foreach ($singers as $singer) {
                SetlistItemSinger::create($singer + ['setlist_item_id' => $item->id]);
            }
        });
    }

    public function reorder(WorshipService $service, array $orderedIds): void
    {
        DB::transaction(function () use ($service, $orderedIds) {
            foreach (array_values($orderedIds) as $order => $id) {
                $this->model->where('worship_service_id', $service->id)->whereKey($id)->update(['sort_order' => $order]);
            }
        });
    }
}
