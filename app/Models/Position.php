<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['ministry_id', 'name', 'emoji', 'uses_microphone', 'min_per_service', 'max_per_service', 'sort_order'])]
class Position extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'uses_microphone' => 'boolean',
            'min_per_service' => 'integer',
            'max_per_service' => 'integer',
        ];
    }

    public function ministry(): BelongsTo
    {
        return $this->belongsTo(Ministry::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class);
    }
}
