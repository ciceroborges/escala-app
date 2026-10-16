<?php

namespace App\Services;

use App\Enums\SongCategoryLevel;
use App\Models\Song;
use App\Repositories\Contracts\IMemberRepository;
use App\Repositories\Contracts\ISongRepository;
use App\Services\Contracts\ISongService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SongService implements ISongService
{
    public function __construct(
        private readonly ISongRepository $songs,
        private readonly IMemberRepository $members,
    ) {}

    public function list(int $ministryId): Collection
    {
        return $this->songs->findByMinistry($ministryId);
    }

    public function find(int $ministryId, int $songId): ?Song
    {
        return $this->songs->findInMinistry($ministryId, $songId);
    }

    public function create(int $ministryId, array $data): Song
    {
        return DB::transaction(function () use ($ministryId, $data) {
            $song = $this->songs->create($this->attributes($ministryId, $data) + ['ministry_id' => $ministryId]);
            $this->songs->replaceKeys($song, $this->keys($ministryId, $data['keys'] ?? []));

            return $song;
        });
    }

    public function update(Song $song, array $data): Song
    {
        return DB::transaction(function () use ($song, $data) {
            $this->songs->update($song, $this->attributes($song->ministry_id, $data));
            $this->songs->replaceKeys($song, $this->keys($song->ministry_id, $data['keys'] ?? []));

            return $song;
        });
    }

    public function delete(Song $song): void
    {
        $this->songs->delete($song);
    }

    public function categories(int $ministryId): Collection
    {
        return $this->songs->findCategories($ministryId);
    }

    public function saveCategories(int $ministryId, array $data): void
    {
        DB::transaction(function () use ($ministryId, $data) {
            foreach (SongCategoryLevel::cases() as $level) {
                $input = $data[$level->value] ?? null;
                if (! $input) {
                    continue;
                }
                $category = $this->songs->upsertCategory($ministryId, $level, [
                    'name' => $input['name'],
                    'active' => (bool) ($input['active'] ?? false),
                ]);
                $this->songs->syncOptions($category, array_filter($input['options'] ?? [], fn ($o) => filled($o['name'] ?? null)));
            }
        });
    }

    private function attributes(int $ministryId, array $data): array
    {
        $option = fn (?int $id, SongCategoryLevel $level) => $id && $this->songs->optionBelongsToMinistry($ministryId, $id, $level) ? $id : null;

        return [
            'title' => $data['title'],
            'artist' => $data['artist'] ?? null,
            'youtube_url' => $data['youtube_url'] ?? null,
            'chords_url' => $data['chords_url'] ?? null,
            'primary_option_id' => $option($data['primary_option_id'] ?? null, SongCategoryLevel::Primary),
            'secondary_option_id' => $option($data['secondary_option_id'] ?? null, SongCategoryLevel::Secondary),
        ];
    }

    /** Só cantores do próprio ministério, um tom por cantor. */
    private function keys(int $ministryId, array $keys): array
    {
        $memberIds = $this->members->findByMinistry($ministryId)->modelKeys();

        return collect($keys)
            ->filter(fn ($k) => in_array((int) ($k['member_id'] ?? 0), $memberIds, true))
            ->unique('member_id')
            ->map(fn ($k) => [
                'member_id' => (int) $k['member_id'],
                'musical_key' => $k['musical_key'] ?: null,
                'starting_key' => $k['starting_key'] ?? null ?: null,
            ])
            ->values()
            ->all();
    }
}
