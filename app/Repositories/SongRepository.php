<?php

namespace App\Repositories;

use App\Enums\SongCategoryLevel;
use App\Models\Song;
use App\Models\SongCategory;
use App\Models\SongCategoryOption;
use App\Models\SongKey;
use App\Repositories\Contracts\ISongRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SongRepository implements ISongRepository
{
    public function __construct(
        private readonly Song $model,
        private readonly SongCategory $categories,
    ) {}

    public function findByMinistry(int $ministryId): Collection
    {
        return $this->model->where('ministry_id', $ministryId)
            ->with(['primaryOption', 'secondaryOption', 'keys'])
            ->orderBy('title')
            ->get();
    }

    public function findInMinistry(int $ministryId, int $songId): ?Song
    {
        return $this->model->where('ministry_id', $ministryId)->with('keys')->find($songId);
    }

    public function create(array $data): Song
    {
        return $this->model->create($data);
    }

    public function update(Song $song, array $data): Song
    {
        $song->update($data);

        return $song;
    }

    public function delete(Song $song): bool
    {
        return (bool) $song->delete();
    }

    public function replaceKeys(Song $song, array $keys): void
    {
        DB::transaction(function () use ($song, $keys) {
            SongKey::where('song_id', $song->id)->delete();
            foreach ($keys as $key) {
                SongKey::create($key + ['song_id' => $song->id]);
            }
        });
    }

    public function findCategories(int $ministryId): Collection
    {
        return $this->categories->where('ministry_id', $ministryId)->with('options')->get();
    }

    public function upsertCategory(int $ministryId, SongCategoryLevel $level, array $data): SongCategory
    {
        return $this->categories->updateOrCreate(
            ['ministry_id' => $ministryId, 'level' => $level],
            $data,
        );
    }

    public function syncOptions(SongCategory $category, array $options): void
    {
        DB::transaction(function () use ($category, $options) {
            $keep = [];
            foreach (array_values($options) as $order => $option) {
                $model = isset($option['id'])
                    ? $category->options()->find($option['id'])
                    : null;
                $data = ['name' => $option['name'], 'emoji' => $option['emoji'] ?? null, 'sort_order' => $order];
                $model = $model ? tap($model)->update($data) : $category->options()->create($data);
                $keep[] = $model->id;
            }
            $category->options()->whereNotIn('id', $keep)->delete();
        });
    }

    public function optionBelongsToMinistry(int $ministryId, int $optionId, SongCategoryLevel $level): bool
    {
        return SongCategoryOption::whereKey($optionId)
            ->whereHas('category', fn ($q) => $q->where('ministry_id', $ministryId)->where('level', $level))
            ->exists();
    }
}
