<?php

namespace App\Services\Contracts;

use App\Models\SetlistItem;
use App\Models\WorshipService;
use Illuminate\Database\Eloquent\Collection;

interface ISetlistService
{
    public function list(WorshipService $service): Collection;

    public function find(WorshipService $service, int $itemId): ?SetlistItem;

    /** Adiciona a música; o tom de cada voz vem do catálogo (música + cantor). */
    public function add(WorshipService $service, array $data): SetlistItem;

    public function update(SetlistItem $item, array $data): SetlistItem;

    public function remove(SetlistItem $item): void;

    public function move(WorshipService $service, SetlistItem $item, string $direction): void;
}
