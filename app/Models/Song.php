<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['ministry_id', 'title', 'artist', 'youtube_url', 'chords_url',
    'primary_option_id', 'secondary_option_id'])]
class Song extends Model
{
    use HasFactory, LogsActivity;

    public function ministry(): BelongsTo
    {
        return $this->belongsTo(Ministry::class);
    }

    public function primaryOption(): BelongsTo
    {
        return $this->belongsTo(SongCategoryOption::class, 'primary_option_id');
    }

    public function secondaryOption(): BelongsTo
    {
        return $this->belongsTo(SongCategoryOption::class, 'secondary_option_id');
    }

    public function keys(): HasMany
    {
        return $this->hasMany(SongKey::class);
    }

    public function activityLabel(): string
    {
        return "música {$this->title}";
    }
}
