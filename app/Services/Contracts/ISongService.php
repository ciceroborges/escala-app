<?php

namespace App\Services\Contracts;

use App\Models\Song;
use Illuminate\Database\Eloquent\Collection;

interface ISongService
{
    public function list(int $ministryId): Collection;

    public function find(int $ministryId, int $songId): ?Song;

    public function create(int $ministryId, array $data): Song;

    public function update(Song $song, array $data): Song;

    public function delete(Song $song): void;

    public function categories(int $ministryId): Collection;

    /** @param array{primary: array, secondary: array} $data */
    public function saveCategories(int $ministryId, array $data): void;
}
