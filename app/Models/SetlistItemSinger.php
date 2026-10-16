<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['setlist_item_id', 'member_id', 'musical_key', 'starting_key'])]
class SetlistItemSinger extends Model
{
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /** "G, mas começa em C" */
    public function keyLabel(): ?string
    {
        if (! $this->musical_key) {
            return null;
        }

        return $this->starting_key ? "{$this->musical_key}, mas começa em {$this->starting_key}" : $this->musical_key;
    }
}
