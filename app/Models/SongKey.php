<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['song_id', 'member_id', 'musical_key', 'starting_key'])]
class SongKey extends Model
{
    public function song(): BelongsTo
    {
        return $this->belongsTo(Song::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
