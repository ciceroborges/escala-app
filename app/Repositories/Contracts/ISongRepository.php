<?php

namespace App\Repositories\Contracts;

use App\Enums\SongCategoryLevel;
use App\Models\Song;
use App\Models\SongCategory;
use Illuminate\Database\Eloquent\Collection;

interface ISongRepository
{
    public function findByMinistry(int $ministryId): Collection;

    public function findInMinistry(int $ministryId, int $songId): ?Song;

    public function create(array $data): Song;

    public function update(Song $song, array $data): Song;

    public function delete(Song $song): bool;

    /** @param array<int, array{member_id: int, musical_key: ?string, starting_key: ?string}> $keys */
    public function replaceKeys(Song $song, array $keys): void;

    public function findCategories(int $ministryId): Collection;

    public function upsertCategory(int $ministryId, SongCategoryLevel $level, array $data): SongCategory;

    /** @param array<int, array{id?: ?int, name: string, emoji: ?string}> $options */
    public function syncOptions(SongCategory $category, array $options): void;

    public function optionBelongsToMinistry(int $ministryId, int $optionId, SongCategoryLevel $level): bool;
}
