<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['worship_service_id', 'song_id', 'sort_order', 'notes'])]
class SetlistItem extends Model
{
    public function worshipService(): BelongsTo
    {
        return $this->belongsTo(WorshipService::class);
    }

    public function song(): BelongsTo
    {
        return $this->belongsTo(Song::class);
    }

    public function singers(): HasMany
    {
        return $this->hasMany(SetlistItemSinger::class);
    }
}
