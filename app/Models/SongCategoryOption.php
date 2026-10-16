<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['song_category_id', 'name', 'emoji', 'sort_order'])]
class SongCategoryOption extends Model
{
    public function category(): BelongsTo
    {
        return $this->belongsTo(SongCategory::class, 'song_category_id');
    }

    public function label(): string
    {
        return trim(($this->emoji ?? '').' '.$this->name);
    }
}
